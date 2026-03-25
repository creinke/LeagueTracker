<?php

namespace App\Controller\Api;

use App\Entity\EventDE;
use App\Entity\GameDE;
use App\Entity\ScoreDE;
use App\Form\GameScoresFormBean;
use App\Form\ScoreBean;
use App\Model\EventFormatType;
use App\Model\EventType;
use App\Repository\EventRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\ScoreRepository;
use App\Repository\TeamgameRepository;
use App\Repository\TeeRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ApiGameController extends AbstractController {
    #[Route('/api/game/list/{eventId}', name: 'api_game_list', methods: ['GET'])]
    public function list(int $eventId, EventRepository $eventRepository): JsonResponse {
        $event = $eventRepository->find($eventId);
        if ( ! $event) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        $userLeagueId = $this->getUser()->getLeague()->getId();
        $eventLeagueId = $event->getSession()->getSeason()->getLeague()->getId();

        if ($eventLeagueId !== $userLeagueId) {
            return new JsonResponse([
                'error'         => 'Access denied: Event does not belong to your league',
                'userLeagueId'  => $userLeagueId,
                'eventLeagueId' => $eventLeagueId
            ], 403);
        }

        $data = [];
        $isTeamEvent = \App\Model\EventType::isTeamEvent($event->getEventtype());

        // We process both teamgames and regular games to be exhaustive and robust
        $teamGames = $event->sortedTeamgames();
        foreach ($teamGames as $game) {
            $players = [];
            foreach ($game->getPlayers() as $tgp) {
                if ($tgp->getPlayer()) {
                    $players[] = [
                        'id'         => $tgp->getPlayer()->getId(),
                        'name'       => $tgp->getPlayer()->getFullname(),
                        'teamNumber' => $tgp->getTeamnumber()
                    ];
                }
            }
            $data[] = [
                'id'           => $game->getId(),
                'type'         => 'TEAM',
                'startingTime' => $game->getStartingtime()?->format('Y-m-d\TH:i:s'),
                'isRecorded'   => $game->isRecorded(),
                'teamNames'    => [(string) $game->getTeamone(), (string) $game->getTeamtwo()],
                'players'      => $players
            ];
        }

        $regularGames = $event->sortedGames();
        foreach ($regularGames as $game) {
            $players = [];
            $teamNames = $this->getEventTeamNames($game);

            // Priority 1: Match Play - players are stored in playermatches
            if ($game->getPlayermatches()->count() > 0) {
                foreach ($game->getPlayermatches() as $match) {
                    if ($match->getPlayerone()) {
                        $players[] = [
                            'id' => $match->getPlayerone()->getId(),
                            'name' => $match->getPlayerone()->getFullname()
                        ];
                    }
                    if ($match->getPlayertwo()) {
                        $players[] = [
                            'id' => $match->getPlayertwo()->getId(),
                            'name' => $match->getPlayertwo()->getFullname()
                        ];
                    }
                }
            } // Priority 2: Team Match events (e.g. Better Ball, Low Team Net)
            elseif ($game->getTeammatches()->count() > 0) {
                foreach ($game->getTeammatches() as $teammatch) {
                    if ($teammatch->getTeamone()) {
                        $players[] = [
                            'id' => $teammatch->getTeamone()->getId(),
                            'name' => $teammatch->getTeamone()->getName(),
                            'isTeam' => true
                        ];
                    }
                    if ($teammatch->getTeamtwo()) {
                        $players[] = [
                            'id' => $teammatch->getTeamtwo()->getId(),
                            'name' => $teammatch->getTeamtwo()->getName(),
                            'isTeam' => true
                        ];
                    }
                }
            } // Priority 3: Direct player associations (often used in Stroke Play or unrecorded games)
            elseif ($game->getPlayers()->count() > 0) {
                foreach ($game->getPlayers() as $player) {
                    $players[] = [
                        'id' => $player->getId(),
                        'name' => $player->getFullname()
                    ];
                }
            }

            $data[] = [
                'id' => $game->getId(),
                'type' => 'REGULAR',
                'startingTime' => $game->getStartingtime()?->format('Y-m-d\TH:i:s'),
                'isRecorded' => $game->isRecorded(),
                'teamNames' => $teamNames,
                'players' => $players
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/game/scores/{gameId}', name: 'api_game_scores_get', methods: ['GET'])]
    public function getScores(int $gameId, GameRepository $gameRepository, TeamgameRepository $teamgameRepository, ScoreRepository $scoreRepository): JsonResponse {
        $game = $gameRepository->find($gameId);
        $teamgame = null;
        if ( ! $game) {
            $teamgame = $teamgameRepository->find($gameId);
        }

        $entity = $game ?: $teamgame;

        if ( ! $entity || $entity->getEvent()->getSession()->getSeason()->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        $event = $entity->getEvent();
        $isTeamGame = ($teamgame !== null);
        $isScramble = $event->isScramble($event->getFormat());

        $nines = [];
        $ninesPlayed = [];
        $ninesPlayed[] = $event->getNine();
        if ($event->getSecondnine()) {
            $ninesPlayed[] = $event->getSecondnine();
        }

        foreach ($ninesPlayed as $nine) {
            $holes = [];
            $tees = $nine->getTees();
            $baseTee = null;
            foreach ($tees as $t) {
                if ($t->getName() === $event->getTee()->getName()) {
                    $baseTee = $t;
                    break;
                }
            }
            if ( ! $baseTee && count($tees) > 0) {
                $baseTee = $tees[0];
            }

            if ($baseTee) {
                foreach ($baseTee->getHoles() as $hole) {
                    $holes[] = [
                        'number'   => $hole->getHolenumber(),
                        'par'      => $hole->getPar(),
                        'handicap' => $hole->getHandicap()
                    ];
                }
            }
            $nines[] = [
                'id'    => $nine->getId(),
                'name'  => $nine->getName(),
                'holes' => $holes
            ];
        }

        if ( ! $isTeamGame) {
            $formBean = new GameScoresFormBean($event, $game);
            $playerScores = [];
            foreach ($formBean->getPlayerScores() as $scoreBean) {
                $availableTees = [];
                foreach ($scoreBean->getTees() as $tee) {
                    $availableTees[] = [
                        'id'   => $tee->getId(),
                        'name' => $tee->getName()
                    ];
                }

                $playerScores[] = [
                    'playerId'      => $scoreBean->getPlayer()->getId(),
                    'playerName'    => $scoreBean->getPlayer()->getFirstname() . ' ' . $scoreBean->getPlayer()->getLastname(),
                    'matchId'       => $scoreBean->getPlayerMatch() ? $scoreBean->getPlayerMatch()->getId() : null,
                    'isPlayed'      => $scoreBean->getPlayed(),
                    'isDuplicate'   => $scoreBean->getDuplicate(),
                    'currentTeeId'  => $scoreBean->getTee()->getId(),
                    'availableTees' => $availableTees,
                    'strokes'       => $scoreBean->getStrokes()
                ];
            }

            return new JsonResponse([
                'gameId'       => $game->getId(),
                'type'         => 'REGULAR',
                'isRecorded'   => $game->isRecorded(),
                'isScramble'   => false,
                'nines'        => $nines,
                'playerScores' => $playerScores
            ]);
        } else {
            $mapTeam = function ($teamNumber, $name, $packedScore) use ($teamgame, $event) {
                $players = [];
                foreach ($teamgame->getPlayers() as $tgp) {
                    if ($tgp->getTeamnumber() == $teamNumber) {
                        $strokes = array_merge($tgp->getFirstnine(), $tgp->getSecondnine());
                        // Truncate to 9 or 18 based on nines played
                        $strokes = array_slice($strokes, 0, $event->getSecondnine() ? 18 : 9);

                        $players[] = [
                            'playerId'      => $tgp->getPlayer()->getId(),
                            'playerName'    => $tgp->getPlayer()->getFirstname() . ' ' . $tgp->getPlayer()->getLastname(),
                            'isPlayed'      => true,
                            'currentTeeId'  => $event->getTee()->getId(),
                            'availableTees' => [],
                            'strokes'       => $strokes
                        ];
                    }
                }
                $teamScore = null;
                if ($packedScore) {
                    $teamScore = ScoreDE::unpack($packedScore);
                    $teamScore = array_slice($teamScore, 0, $event->getSecondnine() ? 18 : 9);
                    foreach ($teamScore as &$s) {
                        if ($s == 15) {
                            $s = null;
                        }
                    }
                }

                return [
                    'name'      => $name,
                    'players'   => $players,
                    'teamScore' => $teamScore
                ];
            };

            return new JsonResponse([
                'gameId'     => $teamgame->getId(),
                'type'       => 'TEAM',
                'isRecorded' => $teamgame->isRecorded(),
                'isScramble' => $isScramble,
                'nines'      => $nines,
                'teamOne'    => $mapTeam(1, $teamgame->getTeamone(), $teamgame->getTeamonescore()),
                'teamTwo'    => $mapTeam(2, $teamgame->getTeamtwo(), $teamgame->getTeamtwoscore()),
            ]);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route('/api/game/scores/{gameId}', name: 'api_game_scores_save', methods: ['POST'])]
    public function saveScores(int $gameId, Request $request, GameRepository $gameRepository, TeamgameRepository $teamgameRepository, PlayerRepository $playerRepository, TeeRepository $teeRepository, ScoreRepository $scoreRepository, \Doctrine\ORM\EntityManagerInterface $entityManager): JsonResponse {
        $game = $gameRepository->find($gameId);

        $teamgame = null;
        if ( ! $game) {
            $teamgame = $teamgameRepository->find($gameId);
        }

        $entity = $game ?: $teamgame;
        if ( ! $entity || $entity->getEvent()->getSession()->getSeason()->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if ( ! $data || ! isset($data['type'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        if ($data['type'] === 'REGULAR') {
            if ( ! $game) {
                return new JsonResponse(['error' => 'Game not found'], 404);
            }
            $event = $game->getEvent();
            $eventType = $event->getEventtype();
            $eventFormat = $event->getFormat();
            $startingdateandtime = $event->getStartdateandtime();
            $singlesMatch = EventType::isSinglesMatch($eventType);
            $matchPlay = EventFormatType::isMatchPlay($eventFormat);

            if ($singlesMatch) {
                $strokePlay = ! $matchPlay;
            } else {
                $gameRepository->reorderPlayerMatchesIfNecessary($event, $game);
            }
            $formBean = new GameScoresFormBean($event, $game);

            foreach ($data['playerScores'] as $postedPlayerScore) {
                $scoreBeans = $formBean->getPlayerScores();

                foreach ($scoreBeans as $scoreBean) {
                    $strokes = $scoreBean->getStrokes();

                    if ($scoreBean->getPlayer()->getId() == $postedPlayerScore['playerId']) {
                        if (isset($postedPlayerScore['currentTeeId'])) {
                            $tee = $teeRepository->find($postedPlayerScore['currentTeeId']);

                            if ($tee) {
                                $scoreBean->setTee($tee);
                            }
                        }
                        if (isset($postedPlayerScore['strokes'])) {
                            $strokes = array_map(function ($s) {
                                if ($s === null || $s === "") {
                                    return 0;
                                }
                                $val = (int) $s;

                                return ($val < 0) ? 0 : (($val > 15) ? 15 : $val);
                            }, $postedPlayerScore['strokes']);
                            $scoreBean->setStrokes($strokes);
                        }
                    }
                }
            }
            $playerScores = $formBean->getPlayerScores();
            foreach ($playerScores as $playerScore) {
                $playerScore->updateState();
            }
            foreach ($playerScores as $playerScore) {
                if ( ! $playerScore->getPlayed()) {
                    $playerScore->duplicatePlayingPartnerScore($formBean);
                }
            }
            foreach ($playerScores as $playerScore) {
                if ( ! $playerScore->getPlayed()) {
                    $playersHighestScore = $this->findHighestScore($playerScore->getPlayer(), $startingdateandtime, $tee, $scoreRepository);
                    $playerScore->updateScore(null, ScoreDE::unpack($playersHighestScore->getStrokes()), true);
                }
            }
            foreach ($playerScores as $playerScore) {
                $score = $this->playerScore($playerScore, $startingdateandtime, $scoreRepository);
                $playerScore->setScore($score);
            }
            if ($singlesMatch && $strokePlay) {
                foreach ($formBean->getPlayerScores() as $scoreBean) {
                    $game->addOrUpdatePlayerScore($scoreBean);
                }
            } else {
                $playerMatches = $game->getPlayermatches();

                foreach ($game->getPlayermatches() as $playerMatch) {
                    foreach ($formBean->getPlayerScores() as $scoreBean) {
                        if ($scoreBean->getPlayerMatch()->getId() == $playerMatch->getId()) {
                            $playerMatch->addOrUpdatePlayerScore($scoreBean);
                        }
                    }
                }
            }
            $game->setRecorded(true);
            $gameRepository->saveGame($game);

            return new JsonResponse(['success' => true, 'message' => 'Scores saved successfully.']);
        } else {
            // TeamGame flow
            if ( ! $teamgame) {
                return new JsonResponse(['error' => 'Game not found'], 404);
            }
            $event = $teamgame->getEvent();
            $isScramble = $event->isScramble($event->getFormat());

            foreach ($data['playerScores'] as $postedPlayerScore) {
                foreach ($teamgame->getPlayers() as $tgp) {
                    if ($tgp->getPlayer()->getId() == $postedPlayerScore['playerId']) {
                        $strokes = $postedPlayerScore['strokes'];
                        $strokes = array_map(function ($s) {
                            return $s === null ? 15 : $s;
                        }, $strokes);
                        $tgp->setFirstnine(array_slice($strokes, 0, 9));
                        if (count($strokes) > 9) {
                            $tgp->setSecondnine(array_slice($strokes, 9, 9));
                        }
                        $entityManager->persist($tgp);
                    }
                }
            }

            if ($isScramble) {
                if (isset($data['teamOneScore'])) {
                    $teamStrokes = array_map(function ($s) {
                        return $s === null ? 15 : $s;
                    }, $data['teamOneScore']);
                    $teamgame->setTeamonescore(ScoreDE::packIntArray($teamStrokes));
                }
                if (isset($data['teamTwoScore'])) {
                    $teamStrokes = array_map(function ($s) {
                        return $s === null ? 15 : $s;
                    }, $data['teamTwoScore']);
                    $teamgame->setTeamtwoscore(ScoreDE::packIntArray($teamStrokes));
                }
            }

            $teamgame->setRecorded(true);
            $teamgameRepository->saveTeamgame($teamgame);

            return new JsonResponse(['success' => true, 'message' => 'Team scores saved successfully.']);
        }
    }

    private function getEventTeamNames(GameDE $game): array {
        $teamMatches = $game->getTeammatches();
        $teamMatch = $teamMatches[0];
        $teamOneName = $teamMatch->getTeamone()->getName();
        $teamTwoName = $teamMatch->getTeamtwo()->getName();

        return [$teamOneName, $teamTwoName];
    }

    #[Route('/api/game/roster/{gameId}', name: 'api_game_roster', methods: ['GET'])]
    public function getRoster(int $gameId, GameRepository $gameRepository): JsonResponse {
        $game = $gameRepository->find($gameId);
        if ( ! $game || $game->getEvent()->getSession()->getSeason()->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        $league = $this->getUser()->getLeague();
        $leagueRoster = [];
        foreach ($league->getPlayers() as $player) {
            if ( ! $player->isDefunct()) {
                $leagueRoster[] = [
                    'id'   => $player->getId(),
                    'name' => $player->getFirstname() . ' ' . $player->getLastname()
                ];
            }
        }

        $currentGamePlayers = [];
        foreach ($game->getMatchPlayers() as $player) {
            $currentGamePlayers[] = [
                'id'   => $player->getId(),
                'name' => $player->getFirstname() . ' ' . $player->getLastname()
            ];
        }

        return new JsonResponse([
            'currentGamePlayers' => $currentGamePlayers,
            'leagueRoster'       => $leagueRoster
        ]);
    }

    #[Route('/api/game/substitute/{gameId}', name: 'api_game_substitute', methods: ['POST'])]
    public function substitute(int $gameId, Request $request, GameRepository $gameRepository, PlayerRepository $playerRepository, \Doctrine\ORM\EntityManagerInterface $entityManager): JsonResponse {
        $game = $gameRepository->find($gameId);
        if ( ! $game || $game->getEvent()->getSession()->getSeason()->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if ( ! isset($data['playerIds']) || ! is_array($data['playerIds'])) {
            return new JsonResponse(['error' => 'Invalid player data'], 400);
        }

        $newPlayerIds = $data['playerIds'];

        // Comprehensive Score Deletion
        foreach ($game->getPlayermatches() as $match) {
            foreach ($match->getPlayerscores() as $score) {
                $entityManager->remove($score);
            }
            $match->getPlayerscores()->clear();
        }

        foreach ($game->getPlayerscores() as $score) {
            $entityManager->remove($score);
        }
        $game->getPlayerscores()->clear();

        // Assign new players to matches
        $playerIndex = 0;
        $matches = $game->getPlayermatches();
        foreach ($matches as $playerMatch) {
            if (isset($newPlayerIds[$playerIndex])) {
                $p1 = $playerRepository->find($newPlayerIds[$playerIndex ++]);
                if ($p1) {
                    $playerMatch->setPlayerone($p1);
                }
            }
            if (isset($newPlayerIds[$playerIndex])) {
                $p2 = $playerRepository->find($newPlayerIds[$playerIndex ++]);
                if ($p2) {
                    $playerMatch->setPlayertwo($p2);
                }
            }
        }

        $game->setRecorded(false);
        $entityManager->flush();

        // Prepare updated roster to return
        $updatedPlayers = [];
        foreach ($game->getMatchPlayers() as $player) {
            if ($player) {
                $updatedPlayers[] = [
                    'id'   => $player->getId(),
                    'name' => $player->getFirstname() . ' ' . $player->getLastname()
                ];
            }
        }

        return new JsonResponse([
            'success'        => true,
            'message'        => 'Players substituted successfully. Existing scores have been reset.',
            'updatedPlayers' => $updatedPlayers
        ]);
    }

    private function findHighestScore(\App\Entity\PlayerDE $player, \DateTime $startingdateandtime, \App\Entity\TeeDE $tee, ScoreRepository $scoreRepository): ?\App\Entity\ScoreDE {
        $scores = $scoreRepository->findPlayerScores($player, $startingdateandtime);
        $highestScore = null;

        foreach ($scores as $score) {
            if ($score->getTee()->getId() == $tee->getId()) {
                if (empty($highestScore)) {
                    $highestScore = $score;
                } else if ($score->getTotalStrokes() > $highestScore->getTotalStrokes()) {
                    $highestScore = $score;
                }
            }
        }
        if (empty($highestScore)) {
            $nine = $tee->getNine();
            foreach ($scores as $score) {
                $scoreNine = $score->getTee()->getNine();
                if (empty($highestScore)) {
                    $highestScore = $score;
                } else if ($scoreNine->getId() == $nine->getId() && $score->getTotalStrokes() > $highestScore->getTotalStrokes()) {
                    $highestScore = $score;
                }
            }
        }
        if (empty($highestScore)) {
            $strokes = [];
            foreach ($tee->getHoles() as $hole) {
                $strokes[] = $hole->getPar() + 2;
            }
            $highestScore = new \App\Entity\ScoreDE();
            $highestScore->setStrokes(\App\Entity\ScoreDE::packIntArray($strokes));
        }

        return $highestScore;
    }

    /**
     * @param ScoreBean $scoreBean
     * @param DateTime $startingdateandtime
     * @param ScoreRepository $scoreRepository
     *
     * @return ScoreDE
     * @throws \Exception
     */
    private function playerScore(ScoreBean $scoreBean, DateTime $startingdateandtime, ScoreRepository $scoreRepository): ScoreDE {
        $playerScore = $scoreBean->getScore();

        if (empty($playerScore)) {
            $playerScore = new ScoreDE();
        }
        if ( ! $playerScore->getDuplicatescore()) {
            $playerScore->setDuplicatescore($scoreBean->getDuplicate());
        }
        $player = $scoreBean->getDuplicate() ? (empty($scoreBean->getSubstitutePlayer()) ? $scoreBean->getPlayer() : $scoreBean->getSubstitutePlayer()) : $scoreBean->getPlayer();

        $playerScore->setPlayer($player);
        $playerScore->setTee($scoreBean->getTee());
        $playerScore->setStrokes(ScoreDE::packIntArray($scoreBean->getStrokes()));
        $playerScore->setTimestamp(clone $startingdateandtime);
        $playerScore->setPartialscore($scoreBean->getPartial());

        $scores = $scoreRepository->findPlayerScores($player, $startingdateandtime);

        if (sizeof($scores) > 20) {
            $scores = array_slice($scores, 0, 20);
        }
        $scoresRecorded = sizeof($scores);
        $playerHandicapCalculationResult = $scoreRepository->calculatePlayerHandicapIndex($player, $startingdateandtime, $scores);

        $playerScore->setCurrenthandicapindex($playerHandicapCalculationResult['currentHandicapIndex']);
        $playerScore->setHandicapdifferential($playerScore->calculateHandicapDifferential($scoresRecorded));

        return $playerScore;
    }

}
