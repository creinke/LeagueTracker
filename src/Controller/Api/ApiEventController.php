<?php
namespace App\Controller\Api;

use App\Entity\EventDE;
use App\Repository\EventRepository;
use App\Repository\SeasonRepository;
use App\Repository\PlayerRepository;
use App\View\SinglesStrokePlayEventViewBean;
use App\View\SinglesMatchPlayEventViewBean;
use App\View\SinglesMatchPlaySeasonStandingsViewBean;
use App\View\SinglesStrokePlaySeasonStandingsViewBean;
use App\View\TeamEventViewBean;
use App\View\SeasonStandingsViewBean;
use App\View\GameResultsViewBean;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ApiEventController extends AbstractController {
    #[Route('/api/event/list/{seasonId}', name: 'api_event_list', methods: ['GET'])]
    public function list(int $seasonId, SeasonRepository $seasonRepository): JsonResponse {
        error_log('Reached EventController::list from Expo');
        $season = $seasonRepository->findById($seasonId);
        if (!$season || $season->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Season not found'], 404);
        }

        $data = [];
        foreach ($season->getSessions() as $session) {
            $events = [];
            foreach ($session->getEvents() as $event) {
                $events[] = [
                    'id' => $event->getId(),
                    'eventNumber' => $event->getEventnumber(),
                    'startDateTime' => $event->getStartdateandtime()?->format('Y-m-d\TH:i:s'),
                    'description' => $event->getDescription(),
                    'format' => $event->getFormatString(),
                ];
            }
            $data[] = [
                'id' => $session->getId(),
                'name' => $session->getName(),
                'events' => $events
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/event/view/{id}', name: 'api_event_view', methods: ['GET'])]
    public function view(int $id, EventRepository $eventRepository): JsonResponse {
        $event = $eventRepository->find($id);
        if (!$event || $event->getSession()->getSeason()->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        $user = $this->getUser();
        $isRegistered = false;
        foreach ($event->getRegistrants() as $registrant) {
            if ($registrant->getFirstname() === $user->getUsername()) {
                $isRegistered = true;
                break;
            }
        }

        return new JsonResponse([
            'id' => $event->getId(),
            'eventNumber' => $event->getEventnumber(),
            'startDateTime' => $event->getStartdateandtime()?->format('Y-m-d\TH:i:s'),
            'description' => $event->getDescription(),
            'course' => $event->getCourse()?->getName(),
            'nine' => $event->getNine()?->getName(),
            'format' => $event->getFormatString(),
            'isWithHandicapping' => $event->isWithhandicapping(),
            'isRegistered' => $isRegistered,
        ]);
    }

    #[Route('/api/event/register/{id}', name: 'api_event_register', methods: ['POST'])]
    public function register(int $id, EventRepository $eventRepository, PlayerRepository $playerRepository): JsonResponse {
        $event = $eventRepository->find($id);
        if (!$event || $event->getSession()->getSeason()->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        $user = $this->getUser();
        $players = $playerRepository->findAll();
        $player = null;
        foreach ($players as $p) {
            if ($p->getFirstname() === $user->getUsername()) {
                $player = $p;
                break;
            }
        }

        if (!$player) {
            return new JsonResponse(['error' => 'No associated player profile found for registration.'], 403);
        }

        if ($event->getRegistrants()->contains($player)) {
            $event->getRegistrants()->removeElement($player);
            $isRegistered = false;
        } else {
            $event->getRegistrants()->add($player);
            $isRegistered = true;
        }

        $eventRepository->saveEvent($event);

        return new JsonResponse([
            'success' => true,
            'isRegistered' => $isRegistered,
            'message' => $isRegistered ? 'Registered successfully.' : 'Unregistered successfully.'
        ]);
    }

    #[Route('/api/event/results/{id}', name: 'api_event_results', methods: ['GET'])]
    public function results(int $id, EventRepository $eventRepository, \Doctrine\ORM\EntityManagerInterface $entityManager, \Psr\Log\LoggerInterface $logger): JsonResponse {
        $event = $eventRepository->find($id);
        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        // Use the user's league to scope the request
        $user = $this->getUser();
        if ($event->getSession()->getSeason()->getLeague()->getId() !== $user->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        $eventType = $event->getEventtype();
        $eventFormat = $event->getFormat();

        // 1. Singles Stroke Play
        if ($event->isSinglesMatch($eventType) && $event->isStrokePlay($eventFormat)) {
            $viewBean = new SinglesStrokePlayEventViewBean($event);
            $viewBean->calculateEventResults($event);

            // Calculate Session/Season points for consistency with web view
            $seasonStandings = new SinglesStrokePlaySeasonStandingsViewBean();
            $season = $event->getSession()->getSeason();
            foreach ($season->getSessions() as $session) {
                $seasonStandings->setSessionPoints([]);
                foreach ($session->getEvents() as $e) {
                    if ($e->isSinglesMatch($e->getEventtype()) && $e->isStrokePlay($e->getFormat())) {
                        if ($e->getGames()->count() > 0 && sizeof($this->unrecordedGames($e)) == 0) {
                            $vb = new SinglesStrokePlayEventViewBean($e);
                            $vb->calculateEventResults($e);
                            $seasonStandings->updatePlayerPoints($vb);
                            if ($e->getId() == $event->getId()) break 2;
                        }
                    }
                }
            }

            $players = array_map(fn($p) => [
                'id' => $p['id'],
                'name' => $p['name'],
                'place' => $p['place'],
                'totalScore' => $p['totalScore'],
                'totalNetScore' => $p['totalNetScore'],
                'score' => $p['score'] ?? [],
                'netScore' => $p['netScore'] ?? [],
                'skins' => $p['skins'] ?? null,
                'sessionPoints' => $p['sessionPoints'] ?? 0,
                'seasonPoints' => $p['seasonPoints'] ?? 0,
            ], $viewBean->players);

            return new JsonResponse([
                'eventId' => $event->getId(),
                'description' => $event->getDescription(),
                'format' => $event->getFormatString(),
                'resultType' => 'SINGLES_STROKE',
                'displayNet' => $viewBean->displayNet,
                'displayTotal' => $viewBean->displayTotal,
                'players' => $players
            ]);
        }

        // 2. Singles Match Play
        if ($event->isSinglesMatch($eventType) && $event->isMatchPlay($eventFormat)) {
            $viewBean = new SinglesMatchPlayEventViewBean($event);
            $viewBean->calculateEventResults($event);

            $seasonStandings = new SinglesMatchPlaySeasonStandingsViewBean();
            $season = $event->getSession()->getSeason();
            foreach ($season->getSessions() as $session) {
                $seasonStandings->setSessionPoints([]);
                foreach ($session->getEvents() as $e) {
                    if ($e->isSinglesMatch($e->getEventtype()) && $e->isMatchPlay($e->getFormat())) {
                        if ($e->getGames()->count() > 0 && sizeof($this->unrecordedGames($e)) == 0) {
                            $vb = new SinglesMatchPlayEventViewBean($e);
                            $vb->calculateEventResults($e);
                            $seasonStandings->updatePlayerPoints($vb);
                            if ($e->getId() == $event->getId()) break 2;
                        }
                    }
                }
            }

            $players = array_map(fn($p) => [
                'id' => $p['id'],
                'name' => $p['name'],
                'matchPoints' => $p['matchPoints'] ?? 0,
                'sessionPoints' => $p['sessionPoints'] ?? 0,
                'seasonPoints' => $p['seasonPoints'] ?? 0,
            ], $viewBean->players);

            $matchups = [];
            foreach ($event->getGames() as $game) {
                foreach ($game->getPlayermatches() as $pm) {
                    $matchups[] = [
                        'teamOne' => $pm->getPlayerone()->getName()->getFullname(),
                        'teamOnePoints' => 50, // $pm->getPlayerOneMatchPoints(),
                        'teamTwo' => $pm->getPlayertwo()->getName()->getFullname(),
                        'teamTwoPoints' => 50, // $pm->getPlayerTwoMatchPoints(),
                    ];
                }
            }

            $teamMatchesData = [];
            foreach ($viewBean->gamePlayerMatchResults as $gameMatchResults) {
                foreach ($gameMatchResults as $playerMatchResults) {
                    $matchData = [];
                    foreach ($playerMatchResults as $playerResultsList) {
                        $playerResultsData = [];
                        foreach ($playerResultsList as $pr) {
                            $playerResultsData[] = [
                                'playerName' => $pr->getPlayerName(),
                                'nineName' => $pr->getNine()->getName(),
                                'handicap' => $pr->getHandicap(),
                                'holeStrokes' => $pr->getHoleStrokes(),
                                'adjustedNetStrokes' => $pr->getAdjustedNetStrokes(),
                                'holePoints' => $pr->getHolePoints(),
                                'holeStrokesTotal' => $pr->getHoleStrokesTotal(),
                                'adjustedHoleStrokesTotal' => $pr->getAdjustedHoleStrokesTotal(),
                                'netStrokesTotal' => $pr->getNetStrokesTotal(),
                                'totalHolePoints' => $pr->getTotalHolePoints(),
                                'netPoints' => $pr->getNetPoints(),
                                'totalPoints' => $pr->getTotalPoints(),
                            ];
                        }
                        $matchData[] = $playerResultsData;
                    }
                    $teamMatchesData[] = $matchData;
                }
            }

            return new JsonResponse([
                'eventId' => $event->getId(),
                'description' => $event->getDescription(),
                'format' => $event->getFormatString(),
                'resultType' => 'SINGLES_MATCH',
                'players' => $players,
                'matchups' => $matchups,
                'teamMatches' => $teamMatchesData
            ]);
        }

        // 3. Team Event
        if ($event->isTeamEvent($eventType)) {
            try {
                $viewBean = new TeamEventViewBean($event, $entityManager, $logger);
                $viewBean->calculateEventResults($event);

                if ($viewBean->isScramble) {
                    $teams = [];
                    foreach ($viewBean->teams as $index => $t) {
                        $teams[] = [
                            'id' => $t['id'] ?? $index,
                            'name' => $t['name'] ?? 'Team',
                            'teamName' => $t['name'] ?? 'Team', // Alias for backward compatibility
                            'players' => array_map(fn($p) => $p['name'], $t['players'] ?? []), // string array for Scramble
                            'playerNames' => array_map(fn($p) => $p['name'], $t['players'] ?? []), // Consistent playerNames
                            'gross' => $t['totalScore'], // Alias for backward compatibility
                            'net' => $t['totalNetScore'], // Alias for backward compatibility
                            'handicap' => $t['handicap'] ?? 0,
                            'totalScore' => $t['totalScore'],
                            'totalNetScore' => $t['totalNetScore'],
                            'place' => $t['place'],
                            'tieBreaker' => $t['tieBreaker'] ?? '',
                            'firstNineScores' => $t['firstNineScores'] ?? [],
                            'firstNineNetScores' => $t['firstNineNetScores'] ?? [],
                            'firstNineTotalScore' => $t['firstNineTotalScore'] ?? 0,
                            'firstNineTotalNetScore' => $t['firstNineTotalNetScore'] ?? 0,
                            'secondNineScores' => $t['secondNineScores'] ?? [],
                            'secondNineNetScores' => $t['secondNineNetScores'] ?? [],
                            'secondNineTotalScore' => $t['secondNineTotalScore'] ?? 0,
                            'secondNineTotalNetScore' => $t['secondNineTotalNetScore'] ?? 0,
                        ];
                    }

                    return new JsonResponse([
                        'eventId' => $event->getId(),
                        'description' => $event->getDescription(),
                        'format' => $event->getFormatString(),
                        'resultType' => 'TEAM_EVENT',
                        'scramble' => true,
                        'withHandicapping' => $viewBean->withHandicapping,
                        'ninesPlayed' => $viewBean->ninesPlayed,
                        'firstNineName' => $event->getNine()?->getName(),
                        'secondNineName' => $event->getSecondnine()?->getName(),
                        'teams' => $teams
                    ]);
                } else {
                    $teams = [];
                    foreach ($viewBean->teams as $index => $t) {
                        $isLowTeamNet = $viewBean->isLowTeamNet;
                        $gross = $isLowTeamNet ? $t['totalTeamScore'] : $t['totalScore'];
                        $net = $isLowTeamNet ? $t['totalTeamNetScore'] : $t['totalNetScore'];
                        
                        $teams[] = [
                            'id' => $t['id'] ?? $index,
                            'name' => $t['name'] ?? 'Team',
                            'teamName' => $t['name'] ?? 'Team', // Alias for backward compatibility
                            'gross' => $gross, // Alias for backward compatibility
                            'net' => $net, // Alias for backward compatibility
                            'place' => $t['place'],
                            'totalScore' => $gross,
                            'totalNetScore' => $net,
                            'tieBreaker' => $t['tieBreaker'] ?? '',
                            'players' => array_map(fn($p) => [
                                'name' => $p['name'],
                                'handicap' => $p['handicap'],
                                'firstNineScores' => $p['firstNineScores'] ?? [],
                                'firstNineNetScores' => $p['firstNineNetScores'] ?? [],
                                'firstNineTotalScore' => $p['firstNineTotalScore'] ?? 0,
                                'firstNineTotalNetScore' => $p['firstNineTotalNetScore'] ?? 0,
                                'secondNineScores' => $p['secondNineScores'] ?? [],
                                'secondNineNetScores' => $p['secondNineNetScores'] ?? [],
                                'secondNineTotalScore' => $p['secondNineTotalScore'] ?? 0,
                                'secondNineTotalNetScore' => $p['secondNineTotalNetScore'] ?? 0,
                            ], $t['players'] ?? []),
                            'playerNames' => array_map(fn($p) => $p['name'], $t['players'] ?? []), // string array for backward compatibility if needed
                            // Team-level hole scores (Better Ball or Team Net)
                            'firstNineScores' => $t['firstNineScores'] ?? [],
                            'firstNineNetScores' => $t['firstNineNetScores'] ?? [],
                            'firstNineTotalTeamScore' => $t['firstNineTotalTeamScore'] ?? 0,
                            'firstNineTotalTeamNetScore' => $t['firstNineTotalTeamNetScore'] ?? 0,
                            'firstNineTotalScore' => $t['firstNineTotalScore'] ?? 0, // for Better Ball
                            'firstNineTotalNetScore' => $t['firstNineTotalNetScore'] ?? 0, // for Better Ball
                            'secondNineScores' => $t['secondNineScores'] ?? [],
                            'secondNineNetScores' => $t['secondNineNetScores'] ?? [],
                            'secondNineTotalTeamScore' => $t['secondNineTotalTeamScore'] ?? 0,
                            'secondNineTotalTeamNetScore' => $t['secondNineTotalTeamNetScore'] ?? 0,
                            'secondNineTotalScore' => $t['secondNineTotalScore'] ?? 0, // for Better Ball
                            'secondNineTotalNetScore' => $t['secondNineTotalNetScore'] ?? 0, // for Better Ball
                        ];
                    }

                    return new JsonResponse([
                        'eventId' => $event->getId(),
                        'description' => $event->getDescription(),
                        'format' => $event->getFormatString(),
                        'resultType' => 'TEAM_EVENT',
                        'scramble' => false,
                        'displayNet' => $viewBean->displayNet,
                        'displayTotal' => $viewBean->displayTotal,
                        'isLowTeamNet' => $viewBean->isLowTeamNet,
                        'withHandicapping' => $viewBean->withHandicapping,
                        'ninesPlayed' => $viewBean->ninesPlayed,
                        'firstNineName' => $event->getNine()?->getName(),
                        'secondNineName' => $event->getSecondnine()?->getName(),
                        'teams' => $teams
                    ]);
                }
            } catch (\Exception $e) {
                $logger->error('Error calculating Team Event results: ' . $e->getMessage(), ['exception' => $e]);
                return new JsonResponse(['error' => 'Error calculating Team Event results: ' . $e->getMessage()], 500);
            }
        }

        // 4. Team Standings (League/Position/Playoff Match)
        if ($event->isTeamMatch($eventType)) {
            $season = $event->getSession()->getSeason();
            $standingsBean = new SeasonStandingsViewBean($season);
            $currentGameResults = null;

            foreach ($season->getSessions() as $session) {
                foreach ($session->getEvents() as $e) {
                    if ($e->isTeamMatch($e->getEventtype())) {
                        if ($e->getGames()->count() > 0 && sizeof($this->unrecordedGames($e)) == 0) {
                            $grvb = new GameResultsViewBean($e);
                            $standingsBean->updateTeamStandingsViewBeans($e, $session->getName(), $grvb);
                            if ($e->getId() == $event->getId()) {
                                $currentGameResults = $grvb;
                                break 2;
                            }
                        }
                    }
                }
            }

            $standingsBean->sortSessionTeamStandings($event->getSession()->getName());
            $sessionStandings = $standingsBean->getSessionTeamStandingsByName($event->getSession());

            $teamResultsData = [];
            if ($currentGameResults) {
                foreach ($currentGameResults->getTeamResults() as $tr) {
                    $teamResultsData[] = [
                        'teamOneName' => $tr->getTeamOneName(),
                        'teamOnePlayerPoints' => $tr->getTeamOnePlayerPoints(),
                        'teamOneNetPoints' => $tr->getTeamOneNetPoints(),
                        'teamOneTotalPoints' => $tr->getTeamOneTotalPoints(),
                        'teamTwoName' => $tr->getTeamTwoName(),
                        'teamTwoPlayerPoints' => $tr->getTeamTwoPlayerPoints(),
                        'teamTwoNetPoints' => $tr->getTeamTwoNetPoints(),
                        'teamTwoTotalPoints' => $tr->getTeamTwoTotalPoints(),
                    ];
                }
            }

            $teamMatchesData = [];
            if ($currentGameResults) {
                foreach ($currentGameResults->getTeamMatches() as $match) {
                    $matchData = [];
                    foreach ($match as $playerMatch) {
                        $playerMatchData = [];
                        foreach ($playerMatch as $pr) {
                            $playerMatchData[] = [
                                'playerName' => $pr->getPlayerName(),
                                'nineName' => $pr->getNine()->getName(),
                                'handicap' => $pr->getHandicap(),
                                'holeStrokes' => $pr->getHoleStrokes(),
                                'adjustedNetStrokes' => $pr->getAdjustedNetStrokes(),
                                'holePoints' => $pr->getHolePoints(),
                                'holeStrokesTotal' => $pr->getHoleStrokesTotal(),
                                'adjustedHoleStrokesTotal' => $pr->getAdjustedHoleStrokesTotal(),
                                'netStrokesTotal' => $pr->getNetStrokesTotal(),
                                'totalHolePoints' => $pr->getTotalHolePoints(),
                                'netPoints' => $pr->getNetPoints(),
                                'totalPoints' => $pr->getTotalPoints(),
                            ];
                        }
                        $matchData[] = $playerMatchData;
                    }
                    $teamMatchesData[] = $matchData;
                }
            }

            $standingsData = [];
            foreach ($sessionStandings as $ts) {
                $teamGames = [];
                if ($currentGameResults) {
                    foreach ($currentGameResults->getTeamResults() as $tr) {
                        if ($tr->getTeamOneName() === $ts->getTeamName()) {
                            $teamGames[] = [
                                'opponent' => $tr->getTeamTwoName(),
                                'points' => $tr->getTeamOneTotalPoints(),
                                'opponentPoints' => $tr->getTeamTwoTotalPoints(),
                                'result' => $tr->getTeamOneTotalPoints() > $tr->getTeamTwoTotalPoints() ? 'Win' : ($tr->getTeamOneTotalPoints() < $tr->getTeamTwoTotalPoints() ? 'Loss' : 'Tie')
                            ];
                        } else if ($tr->getTeamTwoName() === $ts->getTeamName()) {
                            $teamGames[] = [
                                'opponent' => $tr->getTeamOneName(),
                                'points' => $tr->getTeamTwoTotalPoints(),
                                'opponentPoints' => $tr->getTeamOneTotalPoints(),
                                'result' => $tr->getTeamTwoTotalPoints() > $tr->getTeamOneTotalPoints() ? 'Win' : ($tr->getTeamTwoTotalPoints() < $tr->getTeamOneTotalPoints() ? 'Loss' : 'Tie')
                            ];
                        }
                    }
                }

                $standingsData[] = [
                    'teamName' => $ts->getTeamName(),
                    'points' => $ts->getPoints(),
                    'totalPoints' => $ts->getTotalPoints(),
                    'pointsBehind' => $ts->getPointsBehind(),
                    'games' => $teamGames
                ];
            }

            $matchups = [];
            if ($currentGameResults) {
                foreach ($currentGameResults->getTeamResults() as $tr) {
                    $matchups[] = [
                        'teamOne' => $tr->getTeamOneName(),
                        'teamOnePoints' => $tr->getTeamOneTotalPoints(),
                        'teamTwo' => $tr->getTeamTwoName(),
                        'teamTwoPoints' => $tr->getTeamTwoTotalPoints()
                    ];
                }
            }

            return new JsonResponse([
                'eventId' => $event->getId(),
                'description' => $event->getDescription(),
                'format' => $event->getFormatString(),
                'resultType' => 'TEAM_STANDINGS',
                'standings' => $standingsData,
                'matchups' => $matchups,
                'teamResults' => $teamResultsData,
                'teamMatches' => $teamMatchesData,
                'ninesPlayed' => $currentGameResults ? array_map(fn($n) => ['name' => $n->getName()], $currentGameResults->getNinesPlayed()) : []
            ]);
        }

        return new JsonResponse(['error' => 'Results for this event format are not yet available on mobile.'], 400);
    }

    #[Route('/api/event/shortcuts', name: 'api_event_shortcuts', methods: ['GET'])]
    public function shortcuts(): JsonResponse {
        $user = $this->getUser();
        $league = $user->getLeague();
        $dateTime = new \DateTime();
        $dateTime->setTime(0, 0, 0, 0);

        $lastEvent = null;
        $nextEvent = null;

        // Logic for last event (normalized to match EventController::lastEvent)
        $seasons = $league->getSeasons()->toArray();
        usort($seasons, fn($a, $b) => $a->getId() <=> $b->getId());
        foreach($seasons as $season) {
            $sessions = $season->getSessions()->toArray();
            usort($sessions, fn($a, $b) => $a->getId() <=> $b->getId());
            foreach($sessions as $session) {
                $events = $session->getEvents()->toArray();
                usort($events, fn($a, $b) => $a->getEventnumber() <=> $b->getEventnumber());
                foreach($events as $event) {
                    $eventStartTime = clone($event->getStartdateandtime());
                    $eventStartTime->setTime(0, 0, 0, 0);
                    
                    if ($eventStartTime > $dateTime) {
                        break;
                    } else if ($lastEvent == null || $eventStartTime > $lastEvent->getStartdateandtime()) {
                        $lastEvent = $event;
                    }
                }
            }
        }

        // Logic for next event (normalized to match EventController::nextEvent)
        foreach($seasons as $season) {
            $sessions = $season->getSessions()->toArray();
            usort($sessions, fn($a, $b) => $a->getId() <=> $b->getId());
            foreach($sessions as $session) {
                $events = $session->getEvents()->toArray();
                usort($events, fn($a, $b) => $a->getEventnumber() <=> $b->getEventnumber());
                foreach($events as $event) {
                    if ($event->getStartdateandtime() > $dateTime) {
                        $nextEvent = $event;
                        break 2;
                    }
                }
            }
        }

        return new JsonResponse([
            'nextEvent' => $nextEvent ? [
                'id' => $nextEvent->getId(),
                'eventNumber' => $nextEvent->getEventnumber(),
                'description' => $nextEvent->getDescription()
            ] : null,
            'lastEvent' => $lastEvent ? [
                'id' => $lastEvent->getId(),
                'eventNumber' => $lastEvent->getEventnumber(),
                'description' => $lastEvent->getDescription()
            ] : null,
        ]);
    }

    private function unrecordedGames(EventDE $event): array {
        $unrecorded = [];
        foreach ($event->getGames() as $game) {
            if (!$game->isRecorded()) $unrecorded[] = $game;
        }
        return $unrecorded;
    }
}
