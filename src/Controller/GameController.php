<?php

namespace App\Controller;

use App\Entity\EventDE;
use App\Entity\GameDE;
use App\Entity\LeagueDE;
use App\Entity\PlayerDE;
use App\Entity\PlayermatchDE;
use App\Entity\ScoreDE;
use App\Entity\TeeDE;
use App\Form\ChangeGamePlayersFormBean;
use App\Form\GameFormBean;
use App\Form\GameScoresFormBean;
use App\Form\PlayersFormBean;
use App\Form\ScoreBean;
use App\Form\Type\ScoreType;
use App\Model\EventType;
use App\Model\EventFormatType;
use App\Model\GameFormatType;
use App\Repository\EventRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\ScoreRepository;
use App\Repository\TeamRepository;
use App\Utility\Pairings;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 *
 */
class GameController extends AbstractController {
	private EntityManagerInterface $em;
	private LoggerInterface $logger;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		$this->em = $em;
		$this->logger = $logger;
	}

	/**
	 * Build change players form
	 *
	 * @param ChangeGamePlayersFormBean $formbean
	 * @param LeagueDE $league
	 *
	 * @return FormInterface
	 */
    private function buildChangeGamePlayersForm(ChangeGamePlayersFormBean $formbean, LeagueDE $league) : FormInterface {
        $playerChoices = array();
        foreach($league->getPlayers() as $player) {
            $playerChoices[$player->getName()->getFullname()] = $player;
        }

        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        $builder = $this->createFormBuilder($formbean)
            ->add('players', CollectionType::class, ['entry_type' => ChoiceType::class, 'entry_options' => [ 'choices' => $playerChoices, 'attr' => ['style' => 'height: 45px;', 'class' => 'form-control'],],])
            ->add('save', SubmitType::class, array('label' => 'Save Changes', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')));
        
        $form = $builder->getForm();
        return $form;
    }

	/**
	 * Build game scores form
	 *
	 * @param int $eventId
	 * @param $viewOnly
	 * @param GameScoresFormBean $formbean
	 *
	 * @return FormInterface
	 */
    private function buildGameScoresForm(int $eventId, $viewOnly, GameScoresFormBean $formbean) : FormInterface {
        $builder = $this->createFormBuilder($formbean)
            ->add('playerScores', CollectionType::class, array('entry_type' => ScoreType::class, 'entry_options' => array('attr' => array('style' => 'height: 2.5em; width: 2.7em; color: black;')),'required' => true))
            ->add('save', SubmitType::class, array('label' => 'Save Game', 'disabled' => $viewOnly, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')));

        $form = $builder->getForm();
        return $form;
    }

    /**
     * Build generate form
     *
     * @param LeagueDE $league
     * @return FormInterface
     */
    private function buildGenerateSinglesGamesForm(LeagueDE $league, EventDE $event) : FormInterface {
        $formbean = new PlayersFormBean();
        $formbean->setPlayers(new PersistentCollection($this->em, new ClassMetadata('App\Entity\PlayerDE'), new ArrayCollection()));
        $players = [];
        $formbean->setPlayers($players);
        
        $playerChoices = array();
        foreach($league->getPlayers() as $player) {
            $player->registered = $this->playerRegistered($event, $player);
            $playerChoices[$player->getName()->getFullname()] = $player;
            // $formbean->getPlayers()[] = $player;
        }
        
        $disableGenerateButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());
        
        $builder = $this->createFormBuilder($formbean)
            ->add('players', ChoiceType::class, [ 'choices' => $playerChoices, 'expanded' => true, 'multiple' => true,
                'choice_attr' => function ($val, $key, $index) {
                    return ['checked' => $val->registered];
                }])
            ->add('generate', SubmitType::class, array('label' => 'Generate', 'disabled' => $disableGenerateButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')));
        
        $form = $builder->getForm();
        return $form;
    }

	/**
	 * Build singles game form
	 *
	 * @param LeagueDE $league
	 * @param GameFormBean $gameFormBean
	 *
	 * @return FormInterface
	 */
    private function buildSinglesGameForm(LeagueDE $league, GameFormBean $gameFormBean) : FormInterface {
        $playerChoices = array();
        $playerChoices[' '] = NULL;
        
        foreach($league->getPlayers() as $player) {
            $playerChoices[$player->getName()->getFullname()] = $player;
        }
        
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());
        
        $form = $this->createFormBuilder($gameFormBean)
            ->add('startingtime', TimeType::class, array('label' => ' Tee Time', 'widget' => 'single_text', 'required' => true, 'attr' => array('class' => 'form-control', 'style' => 'height: 45px;')))
            ->add('players', CollectionType::class, ['entry_type' => ChoiceType::class, 'entry_options' => [ 'choices' => $playerChoices, 'attr' => ['style' => 'width:100%; height: 45px;', 'class' => 'form-control'],],])
            ->add('save', SubmitType::class, array('label' => 'Save Game', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')))
            ->getForm();
                                
        return $form;
    }

	/**
	 * Build team game form
	 *
	 * @param GameDE $game
	 * @param LeagueDE $league
	 *
	 * @return FormInterface
	 */
    private function buildTeamGameForm(GameDE $game, LeagueDE $league) : FormInterface {
        $teamChoices = array();
        foreach($league->getTeams() as $team) {
            $teamChoices[$team->getName()] = $team->getId();
        }

        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        $form = $this->createFormBuilder($game)
            ->add('startingtime', TimeType::class, array('label' => ' Tee Time', 'widget' => 'single_text', 'required' => true, 'attr' => array('class' => 'form-control', 'style' => 'height: 45px;')))
            ->add('teamOneId', ChoiceType::class, array('label' => 'Team One', 'required' => true, 'attr' => array('class' => 'form-control', 'style' => 'height: 45px;'),
                'choices'  => $teamChoices))
            ->add('teamTwoId', ChoiceType::class, array('label' => 'Team Two', 'required' => true, 'attr' => array('class' => 'form-control', 'style' => 'height: 45px;'),
                'choices'  => $teamChoices))
            ->add('save', SubmitType::class, array('label' => 'Save Game', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')))
            ->getForm();

        return $form;
    }

	/**
	 * @param Request $request
	 * @param $event_id
	 * @param $game_id
	 * @param $gamenumber
	 *
	 * @return RedirectResponse|Response
	 */
	#[Route('/game/change/players/{event_id}/{game_id}/{gamenumber}', name: 'game_change_players', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_USER')]
	public function changePlayers(Request $request, $event_id, $game_id, $gamenumber): RedirectResponse|Response {
        $user = $this->getUser();
        $league = $user->getLeague();

		$gameRepository = new GameRepository($this->em, $this->logger);

		$game = $gameRepository->find($game_id);

        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($event_id);

        $formbean = new ChangeGamePlayersFormBean($league, $game->getMatchPlayers());
        $form = $this->buildChangeGamePlayersForm($formbean, $league);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formbeanPlayers = $formbean->getPlayers();
            $formbeanPlayerIndex = 0;
            $changedPlayers = false; 
            
            foreach($game->getPlayermatches() as $playerMatch) {
                if ($playerMatch->getPlayerone()->getId() != $formbeanPlayers[$formbeanPlayerIndex]->getId() || $playerMatch->getPlayertwo()->getId() != $formbeanPlayers[$formbeanPlayerIndex + 1]->getId()) {
                    $changedPlayers = true;
                    $game->setRecorded(false);
                    break;
                }
            }
            if ($changedPlayers) {
                try {
                    foreach($game->getPlayermatches() as $playerMatch) {
                        $playerOneScores = $playerMatch->getPlayeronescores();
                        
                        foreach($playerOneScores as $score) {
                            $this->em->remove($score);
                            $playerMatch->getPlayerscores()->removeElement($score);
                        }
                        $playerTwoScores = $playerMatch->getPlayertwoscores();
                        
                        foreach($playerTwoScores as $score) {
                            $this->em->remove($score);
                            $playerMatch->getPlayerscores()->removeElement($score);
                        }
                    }
                    $formbeanPlayerIndex = 0;
                    $playerRepository = new PlayerRepository($this->em, $this->logger);
                    
                    foreach($game->getPlayermatches() as $playerMatch) {
                        $player = $playerRepository->find($formbeanPlayers[$formbeanPlayerIndex++]->getId());
                        $playerMatch->setPlayerone($player);
                        $player = $playerRepository->find($formbeanPlayers[$formbeanPlayerIndex++]->getId());
                        $playerMatch->setPlayertwo($player);
                    }
                    $gameRepository->saveGame($game);
                    $parameters = array('event_id' => $event_id, 'game_id' => $game_id, 'gamenumber' => $gamenumber);
                    return $this->redirectToRoute('post_scores', $parameters);
                } catch (Exception $e) {
                    $form->addError(new FormError('Trouble updating selected game: ' . $e->getMessage() . ' Please retry.'));
                }
            }
        }
        return $this->render('game/change.players.html.twig',
            array(
                'title' => "Change Players",
                'event' => $event,
                'game' => $game,
                'gamenumber' => $gamenumber,
                'form' => $form->createView()));
    }

	/**
	 * @param Request $request
	 * @param $event_id
	 * @param $game_id
	 *
	 * @return RedirectResponse
	 * @throws Exception
	 */
	#[Route('/game/delete/{event_id}/{game_id}', name: 'game_delete', methods: ['DELETE'])]
	#[IsGranted('ROLE_ADMIN')]
	public function delete(Request $request, $event_id, $game_id): RedirectResponse {
        $gameRepository = new GameRepository($this->em, $this->logger);
        $game = $gameRepository->find($game_id);
        $gameRepository->removeGame($game);

        $parameters = array('id' => $event_id);
        return $this->redirectToRoute('event_edit', $parameters);
    }

	/**
	 * @param PlayerDE $player
	 * @param DateTime $startingdateandtime
	 * @param TeeDE $tee
	 *
	 * @return ScoreDE|NULL
	 * @throws Exception
	 */
    private function findHighestScore(PlayerDE $player, DateTime $startingdateandtime, TeeDE $tee) : ?ScoreDE {
        $scoreRepository = new ScoreRepository($this->em, $this->logger);
        $scores = $scoreRepository->findPlayerScores($player, $startingdateandtime);
        $highestScore = null;

        foreach($scores as $score) {
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
            
            foreach($scores as $score) {
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
            
            foreach($tee->getHoles() as $hole) {
                $strokes[] = $hole->getPar() + 2;
            }
            $highestScore = new ScoreDE();
            $highestScore->setStrokes(ScoreDE::packIntArray($strokes));
        }
        return $highestScore;
    }

	/**
	 * @param Request $request
	 * @param $event_id
	 * @param $game_id
	 * @param $gamenumber
	 *
	 * @return Response
	 */
	#[Route('/game/edit/{event_id}/{game_id}/{gamenumber}', name: 'game_edit', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_ADMIN')]
	public function edit(Request $request, $event_id, $game_id, $gamenumber): Response {
        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($event_id);
        
        if (EventType::isSinglesMatch($event->getEventtype())) {
            return $this->editSinglesGame($request, $eventRepository, $event, $game_id, $gamenumber);
        } else {
            return $this->editTeamGame($request, $eventRepository, $event, $game_id, $gamenumber);
        }
    }
    
    /**
     * @param Request $request
     * @param EventRepository $eventRepository
     * @param EventDE $event
     * @param int $game_id
     * @param int $gamenumber
     * @return Response
     */
    private function editSinglesGame(Request $request, EventRepository $eventRepository, EventDE $event, $game_id, $gamenumber) : Response {
        $user = $this->getUser();
        $league = $user->getLeague();
        
        $gameRepository = new GameRepository($this->em, $this->logger);
        $game = $gameRepository->find($game_id);
        
        if (EventFormatType::isMatchPlay($event->getFormat())) {
            $matchPlay = true;
            $form = $this->buildSinglesGameForm($league, new GameFormBean($league, $game->getMatchPlayers(), $game->getStartingtime()));
        } else {
            $matchPlay = false;
            $players = $game->getPlayers()->toArray();
            $newPlayerCount = $event->getPlayersperteam() * $event->getTeamsorplayerspergame() - sizeof($players);
            while ($newPlayerCount-- > 0) {
                $players[] = new PlayerDE($this->em);
            }
            $form =$this->buildSinglesGameForm($league, new GameFormBean($league, $players, $game->getStartingtime()));
        }
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $players = $form->getData()->getPlayers();
                $game->setStartingtime($form->getData()->getStartingtime());
                
                if ($matchPlay) {
                    $playerIndex = 0;
                    $changedPlayers = false;
                    
                    foreach($game->getPlayermatches() as $playerMatch) {
                        if ($playerMatch->getPlayerone()->getId() != $players[$playerIndex]->getId() || $playerMatch->getPlayertwo()->getId() != $players[$playerIndex + 1]->getId()) {
                            $changedPlayers = true;
                            $game->setRecorded(false);
                            break;
                        }
                    }
                    if ($changedPlayers) {
                        foreach($game->getPlayermatches() as $playerMatch) {
                            $playerOneScores = $playerMatch->getPlayeronescores();
                            
                            foreach($playerOneScores as $score) {
                                $this->em->remove($score);
                                $playerMatch->getPlayerscores()->removeElement($score);
                            }
                            $playerTwoScores = $playerMatch->getPlayertwoscores();
                            
                            foreach($playerTwoScores as $score) {
                                $this->em->remove($score);
                                $playerMatch->getPlayerscores()->removeElement($score);
                            }
                        }
                        $playerIndex = 0;
                        $playerRepository = new PlayerRepository($this->em, $this->logger);
                        
                        foreach($game->getPlayermatches() as $playerMatch) {
                            $player = $playerRepository->find($players[$playerIndex++]->getId());
                            $playerMatch->setPlayerone($player);
                            $player = $playerRepository->find($players[$playerIndex++]->getId());
                            $playerMatch->setPlayertwo($player);
                        }
                    }
                } else {
                    foreach($game->getPlayers() as $player) {
                        $gamePlayerKeys[$player->getId()] = $player;
                    }
                    foreach($players as $player) {
                        if ($player != null) {
                            $formPlayerKeys[$player->getId()] = $player;
                        }
                    }
                    $playerRepository = new PlayerRepository($this->em, $this->logger);
                    
                    $newPlayers = array_diff_key($formPlayerKeys, $gamePlayerKeys);
                    $deletedPlayers = array_diff_key($gamePlayerKeys, $formPlayerKeys);
                    
                    foreach($deletedPlayers as $deletedPlayer) {
                        foreach($game->getPlayerscores() as $score) {
                            if ($score->getPlayer()->getId() == $deletedPlayer->getId()) {
                                $this->em->remove($score);
                                $game->getPlayerscores()->removeElement($score);
                            }
                        }
                        $game->getPlayers()->removeElement($deletedPlayer);
                    }
                    if (sizeof($newPlayers) > 0) {
                        foreach($newPlayers as $newPlayer) {
                            $player = $playerRepository->find($newPlayer->getId());
                            $game->getPlayers()->add($player);
                        }
                    }
                }
                $gameRepository->saveGame($game);
                
                $parameters = array('id' => $event->getId());
                return $this->redirectToRoute('event_edit', $parameters);
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected game: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        $eventStartdateandtime = $event->getStartdateandtime();
        $gameStartingtime = $game->getStartingtime();
        
        return $this->render('game/editsingles.html.twig',
            [
                'title' => "Edit Game",
                'eventStartdateandtime' => $eventStartdateandtime,
                'gamenumber' => $gamenumber,
                'gameStartingtime' => $gameStartingtime,
                'form' => $form->createView(),
                'matchPlay' => $matchPlay
            ]
        );
    }
    
    /**
     * @param Request $request
     * @param EventRepository $eventRepository
     * @param EventDE $event
     * @param int $game_id
     * @param int $gamenumber
     * @return Response
     */
    private function editTeamGame(Request $request, EventRepository $eventRepository, EventDE $event, $game_id, $gamenumber) : Response {
        $user = $this->getUser();
        $league = $user->getLeague();

        $gameRepository = new GameRepository($this->em, $this->logger);
        $game = $gameRepository->find($game_id);
        $teamOneId = $game->getTeamMatches()[0]->getTeamone()->getId();
        $game->setTeamOneId($teamOneId);
        $teamTwoId = $game->getTeamMatches()[0]->getTeamtwo()->getId();
        $game->setTeamTwoId($teamTwoId);

        $form = $this->buildTeamGameForm($game, $league);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $teamRepository = new TeamRepository($this->em, $this->logger);
                
                if ($teamOneId != $game->getTeamOneId() || $teamTwoId != $game->getTeamTwoId()) {
                    if ($teamOneId != $game->getTeamoneId()) {
                        $team = $teamRepository->find($game->getTeamOneId());
                        $game->getTeamMatches()[0]->setTeamone($team);
                    }
                    if ($teamTwoId != $game->getTeamTwoId()) {
                        $team = $teamRepository->find($game->getTeamTwoId());
                        $game->getTeamMatches()[0]->setTeamtwo($team);
                    }
                    foreach($game->getPlayermatches() as $playerMatch) {
                        foreach($playerMatch->getPlayerscores() as $score) {
                            $this->em->remove($score);
                        }
                    }
                    $game->getPlayermatches()->clear();
                    $game->setPlayermatches(new PersistentCollection($this->em, new ClassMetadata('App\Entity\PlayermatchDE'), new ArrayCollection()));
                    
                    $playerRepository = new PlayerRepository($this->em, $this->logger);
                    $teamOne = $teamRepository->find($game->getTeamOneId());
                    $teamTwo = $teamRepository->find($game->getTeamTwoId());
                    
                    for ($playerIndex = 0; $playerIndex < $teamOne->getPlayers()->count(); $playerIndex++) {
                        $playerOne = $playerRepository->find($teamOne->getPlayers()->get($playerIndex)->getId());
                        $playerTwo = $playerRepository->find($teamTwo->getPlayers()->get($playerIndex)->getId());
                        
                        $playerMatch = new PlayermatchDE();
                        $playerMatch->setGame($game);
                        $playerMatch->setPlayerone($playerOne);
                        $playerMatch->setPlayertwo($playerTwo);
                        
                        $game->getPlayermatches()->add($playerMatch);
                    }
                }
                $gameRepository->saveGame($game);
                
                $parameters = array('id' => $event->getId());
                return $this->redirectToRoute('event_edit', $parameters);
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected game: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        $eventStartdateandtime = $event->getStartdateandtime();
        $gameStartingtime = $game->getStartingtime();

        return $this->render('game/edit.html.twig',
            [
                'title' => "Edit Game",
                'eventStartdateandtime' => $eventStartdateandtime,
                'gamenumber' => $gamenumber,
                'gameStartingtime' => $gameStartingtime,
                'form' => $form->createView()
            ]
        );
    }

	/**
	 * @param Request $request
	 * @param int $event_id event id
	 *
	 * @return Response
	 * @throws Exception
	 */
	#[Route('/game/generate/{event_id}', name: 'game_generate', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_ADMIN')]
	public function generate(Request $request, int $event_id): Response {
        $user = $this->getUser();
        $league = $user->getLeague();
        
        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($event_id);
        
        $eventType = $event->getEventtype();
        $eventFormat = $event->getFormat();
        $playersPerTeam = $event->getPlayersperteam();
        $teamsOrPlayersPerGame = $event->getTeamsorplayerspergame();
        
        if (EventType::isTeamMatch($eventType)) {
            $gameFormat = GameFormatType::toOrdinal(GameFormatType::SINGLES_MATCH_PLAY);
            $teams = $league->getCurrentlyActiveTeams();
            $pairings = Pairings::generateTeanMatchPairings($teams, 1);
            $startGeneratingPairings = true; 
        } else {
            $form = $this->buildGenerateSinglesGamesForm($league, $event);
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $pairings = [];
                    $players = $form->getData()->getPlayers();
                    
                    if (EventFormatType::isStrokePlay($eventFormat)) {
                        $gameFormat = GameFormatType::toOrdinal(GameFormatType::SINGLES_STROKE_PLAY);
                        $pairings = Pairings::generateRandomSinglesPairings($players, 1, $teamsOrPlayersPerGame);
                    } else {
                        $gameFormat = GameFormatType::toOrdinal(GameFormatType::SINGLES_MATCH_PLAY);
                        $pairings = Pairings::generateRandomTeamPairings($players, 1, $playersPerTeam);
                    }
                    $startGeneratingPairings = true;
                } catch (Exception $e) {
                    $form->addError(new FormError('Trouble generating games: ' . $e->getMessage() . ' Please retry.'));
                }
            } else {
                $startGeneratingPairings = false;
            }
        }
        if ($startGeneratingPairings) {
            $minutesBetweenGames = $event->getMinutesbetweengames();
            $eventDateAndTime = $event->getStartdateandtime();
            $teeDateAndTime = clone $eventDateAndTime;
            
            for ($gameNumber = 0; $gameNumber < sizeof($pairings[0]); $gameNumber++) {
                $gameDateAndTime = clone $teeDateAndTime;
                
                iF (EventType::isTeamMatch($eventType)) {
                    $teamRepository = new TeamRepository($this->em, $this->logger);
                    $teamPairings = [];
                    $teamPairings[] = $teamRepository->find($pairings[0][$gameNumber][0]->getId());
                    $teamPairings[] = $teamRepository->find($pairings[0][$gameNumber][1]->getId());
                    
                    $game = SeasonController::createNewTeamMatchGame(new GameDE($this->em), $event, $this->em, $this->logger, $teamPairings, $gameDateAndTime, $gameFormat);
                } else {
                    if (EventFormatType::isStrokePlay($eventFormat)) {
                        $game = SeasonController::createNewSinglesGame(new GameDE($this->em), $event, $this->em, $this->logger, $pairings[0][$gameNumber], $gameDateAndTime, $gameFormat);

                        /*
                            Add scores to generated game 
                            
                        $tees = [];
                        $tees[] = $event->getNine()->findTeeByName($event->getTee()->getName());
                        
                        if (!empty($event->getSecondnine())) {
                            $tees[] = $event->getSecondnine()->findTeeByName($event->getTee()->getName());
                        }
                        $game->setRecorded(true);
                        $scoreRepository = new ScoreRepository($this->em, $this->logger);
                        
                        foreach($game->getPlayers() as $player) {
                            $scores = $scoreRepository->findAllPlayerScores($player, new DateTime());
                            
                            foreach($tees as $tee) {
                                $scoreAdded = false;
                                
                                while (!$scoreAdded) {
                                    $randomScoreIndex = random_int(0, sizeof($scores) - 1);
                                    $score = $scores[$randomScoreIndex];
                                    
                                    if ($tee->getId() == $score->getTee()->getId() && !$score->getDuplicatescore() && !$score->getPartialscore()) {
                                        $game->getPlayerscores()->add($score);
                                        $scoreAdded = true;
                                    }
                                }
                            }
                        }
                        */

                    } else {
                        $game = SeasonController::createNewSinglesTeamMatchGame(new GameDE($this->em), $event, $this->em, $this->logger, $pairings[0][$gameNumber], $gameDateAndTime, $gameFormat);
                        
                        /*
                            Add scores to generated game
                         
                        $tees = [];
                        $tees[] = $event->getNine()->findTeeByName($event->getTee()->getName());
                        
                        if (!empty($event->getSecondnine())) {
                            $tees[] = $event->getSecondnine()->findTeeByName($event->getTee()->getName());
                        }
                        $game->setRecorded(true);
                        $scoreRepository = new ScoreRepository($this->em, $this->logger);
                        
                        foreach($game->getPlayermatches() as $playerMatch) {
                            $players = [];
                            $players[] = $playerMatch->getPlayerone();
                            $players[] = $playerMatch->getPlayertwo();
                            
                            $playerScores = [];
                            foreach($players as $player) {
                                $scores = $scoreRepository->findAllPlayerScores($player, new DateTime());
                                
                                foreach($tees as $tee) {
                                    $scoreAdded = false;
                                    
                                    while (!$scoreAdded) {
                                        $randomScoreIndex = random_int(0, sizeof($scores) - 1);
                                        $score = $scores[$randomScoreIndex];
                                        
                                        if ($tee->getId() == $score->getTee()->getId() && !$score->getDuplicatescore() && !$score->getPartialscore()) {
                                            $playerScores[] = $score;
                                            $scoreAdded = true;
                                        }
                                    }
                                }
                            }
                            $playerMatch->setPlayerscores($playerScores);
                        }
                        */
                    }
                }
                $event->getGames()->add($game);
                $teeDateAndTime->modify('+' . $minutesBetweenGames . ' minutes');
            }
            $eventRepository->saveEvent($event);
            
            $parameters = array('id' => $event_id);
            return $this->redirectToRoute('event_edit', $parameters);
        }
        return $this->render('game/generate.html.twig',
            [
                'title' => "Generate Games",
                'event' => $event,
                'expanded' => true,
                'multiple' => true,
                'form' => $form->createView()
            ]
        );
    }
    
    /**
     * @param Form $form
     * @param GameScoresFormBean $formbean
     * @return boolean valid form
     */
    private function isValid(Form $form, GameScoresFormBean $formbean): bool {
        foreach($formbean->getPlayerScores() as $playerScores) {
            foreach($playerScores as $score) {
                if (!is_numeric($score)) {
                    $form->addError(new FormError('All player scores must be numeric; please fix scorecard entries as required and retry.'));
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @return EventDE id
     */
    private function lastEvent(): EventDE|int {
        $user = $this->getUser();
        $league = $user->getLeague();
        $dateTime =  new DateTime();
        $lastEvent = null;
        
        foreach($league->getSeasons() as $season) {
            foreach($season->getSessions() as $session) {
                foreach($session->getEvents() as $event) {
                    $eventStartTime = clone($event->getStartdateandtime());
                    $eventStartTime->setTime(0, 0, 0, 0);
                    
                    if ($eventStartTime > $dateTime) {
                        return $lastEvent->getId();
                    } else {
                        $lastEvent = $event;
                    }
                }
            }
        }
        if (empty($lastEvent)) {
            return 0;
        } else {
            return $lastEvent->getId();
        }
    }
    
    /**
     * @param Request $request
     * @param int $event_id event id
     *
     * @return Response
     */
	#[Route('/game/new/{event_id}', name: 'game_new', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_ADMIN')]
	public function new(Request $request, int $event_id): Response {
        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($event_id);

        if (EventType::isSinglesMatch($event->getEventtype())) {
            return $this->newSinglesGame($request, $eventRepository, $event);
        } else {
            return $this->newTeamMatchGame($request, $eventRepository, $event);
        }
    }

    /**
     * @param Request $request
     * @param EventRepository $eventRepository
     * @param EventDE $event
     * @return Response
     */
    private function newSinglesGame(Request $request, EventRepository $eventRepository, EventDE $event) : Response {
        $user = $this->getUser();
        $league = $user->getLeague();
        
        $game = new GameDE($this->em);
        
        if (EventFormatType::isMatchPlay($event->getFormat())) {
            $matchPlay = true;
            $playerCount = $event->getPlayersperteam() * $event->getTeamsorplayerspergame();
        } else {
            $matchPlay = false;
            $playerCount = $event->getTeamsorplayerspergame();
        }
        while ($playerCount-- > 0) {
            $players[] = new PlayerDE($this->em);
        }
        $form = $this->buildSinglesGameForm($league, new GameFormBean($league, $players, clone($event->getStartdateandtime())));
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $game = new GameDE($this->em);
                $game->setStartingtime($form->getData()->getStartingtime());
                $game->setEvent($event);
                
                $players = $form->getData()->getPlayers();
                $playerCount = sizeof($players);
    
                $playerRepository = new PlayerRepository($this->em, $this->logger);
                
                if ($matchPlay) {
                    $game->setFormat(GameFormatType::toOrdinal(GameFormatType::SINGLES_MATCH_PLAY));
                    
                    for ($playerIndex = 0; $playerIndex < $playerCount; $playerIndex += 2) {
                        $playerOne = $playerRepository->find($players[$playerIndex]->getId());
                        $playerTwo = $playerRepository->find($players[$playerIndex + 1]->getId());
                        
                        $playerMatch = new PlayermatchDE();
                        $playerMatch->setGame($game);
                        $playerMatch->setPlayerone($playerOne);
                        $playerMatch->setPlayertwo($playerTwo);
                        
                        $game->getPlayermatches()->add($playerMatch);
                    }
                } else {
                    $game->setFormat(GameFormatType::toOrdinal(GameFormatType::SINGLES_STROKE_PLAY));
                    
                    for ($playerIndex = 0; $playerIndex < $playerCount; $playerIndex++) {
                        $id = $players[$playerIndex]->getId();
                        
                        if ($id != NULL) {
                            $player = $playerRepository->find($id);
                            $game->getPlayers()->add($player);
                        }
                    }
                }
                $event->getGames()->add($game);
                $eventRepository->saveEvent($event);
                
                $parameters = array('id' => $event->getId());
                return $this->redirectToRoute('event_edit', $parameters);
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble adding game: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        return $this->render('game/newsingles.html.twig',
            [
                'title' => "Add Game",
                'event' => $event,
                'matchPlay' => $matchPlay,
                'form' => $form->createView()
            ]
        );
    }
    
    /**
     * @param Request $request
     * @param EventRepository $eventRepository
     * @param EventDE $event
     * @return Response
     */
    private function newTeamMatchGame(Request $request, EventRepository $eventRepository, EventDE $event) : Response {
        $user = $this->getUser();
        $league = $user->getLeague();
        
        $g = new  GameDE($this->em);
        
        $form = $this->buildTeamGameForm($g, $league);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $g = $form->getData();
            
            $game = new GameDE($this->em);
            $gameFormat = GameFormatType::toOrdinal(GameFormatType::SINGLES_MATCH_PLAY);
            $gameDateAndTime = clone($g->getStartingtime());
            
            $teamRepository = new TeamRepository($this->em, $this->logger);
            $pairings[] = $teamRepository->find($g->getTeamOneId());
            $pairings[] = $teamRepository->find($g->getTeamTwoId());
            
            $game = SeasonController::createNewTeamMatchGame($game, $event, $this->em, $this->logger, $pairings, $gameDateAndTime, $gameFormat);
            
            $event->getGames()->add($game);
            $eventRepository->saveEvent($event);
            
            $parameters = array('id' => $event->getId());
            return $this->redirectToRoute('event_edit', $parameters);
        }
        return $this->render('game/new.html.twig',
            [
                'title' => "Add Game",
                'event' => $event,
                'form' => $form->createView()
            ]
        );
    }
    
    /**
     * @param EventDE $event
     * @param PlayerDE $player
     * @return boolean if player is registered to play in event
     */
    private function playerRegistered(EventDE $event, PlayerDE $player): bool {
        $player_id = $player->getId();
        
        foreach($event->getRegistrants() as $registrant) {
            if ($registrant->getId() == $player_id) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * @param ScoreBean $scoreBean
     * @param DateTime $startingdateandtime
     * @return ScoreDE
     */
    private function playerScore(ScoreBean $scoreBean, DateTime $startingdateandtime): ScoreDE {
        $playerScore = $scoreBean->getScore();
        
        if (empty($playerScore)) {
            $playerScore = new ScoreDE();
        }
        if (!$playerScore->getDuplicatescore()) {
            $playerScore->setDuplicatescore($scoreBean->getDuplicate());
        }
        $player = $scoreBean->getDuplicate() ? (empty($scoreBean->getSubstitutePlayer()) ? $scoreBean->getPlayer() : $scoreBean->getSubstitutePlayer()) : $scoreBean->getPlayer();
        
        $playerScore->setPlayer($player);
        $playerScore->setTee($scoreBean->getTee());
        $playerScore->setStrokes(ScoreDE::packIntArray($scoreBean->getStrokes()));
        $playerScore->setTimestamp(clone $startingdateandtime);
        $playerScore->setPartialscore($scoreBean->getPartial());
        
        $scoreRepository = new ScoreRepository($this->em, $this->logger);
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

	/**
	 * @param Request $request
	 * @param $gamenumber
	 * @param $event_id
	 * @param $game_id
	 *
	 * @return RedirectResponse|Response
	 * @throws Exception
	 */
	#[Route('/game/post/scores/{event_id}/{game_id}/{gamenumber}', name: 'post_scores', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_USER')]
	public function postScores(Request $request, $gamenumber, $event_id, $game_id): RedirectResponse|Response {
        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($event_id);

        $tee = $event->getTee();
        $startdateandtime = $event->getStartdateandtime();
        $singlesMatch = EventType::isSinglesMatch($event->getEventtype());
        $matchPlay = EventFormatType::isMatchPlay($event->getFormat());
        
        $season = $event->getSession()->getSeason();
        
        $gameRepository = new GameRepository($this->em, $this->logger);
        $game = $gameRepository->find($game_id);
            
        if ($singlesMatch) {
            $strokePlay = !$matchPlay;
        } else {
            $gameRepository->reorderPlayerMatchesIfNecessary($event, $game);
        }
        $formbean = new GameScoresFormBean($event, $game);
        
        $user = $this->getUser();
        $roles = $user->getRoles();
        
        if (!(in_array("ROLE_SUPER", $roles) || in_array("ROLE_ADMIN", $roles))) {
            $lastEventId = $this->lastEvent();
            $viewOnly = $event_id != $lastEventId;
        } else {
            $viewOnly = in_array('ROLE_SAMPLE', $roles);
        }
        
        $form = $this->buildGameScoresForm($event_id, $viewOnly, $formbean);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isValid($form, $formbean)) {
                foreach($formbean->getPlayerScores() as $playerScore) {
                    $playerScore->updateState();
                }
                foreach($formbean->getPlayerScores() as $playerScore) {
                    if (!$playerScore->getPlayed()) {
                        $playerScore->duplicatePlayingPartnerScore($formbean);
                    }
                }
                foreach($formbean->getPlayerScores() as $playerScore) {
                    if (!$playerScore->getPlayed()) {
                        $playersHighestScore = $this->findHighestScore($playerScore->getPlayer(), $startdateandtime, $tee);
                        $playerScore->updateScore(null, ScoreDE::unpack($playersHighestScore->getStrokes()), true);
                    }
                }
                foreach($formbean->getPlayerScores() as $playerScore) {
                    $score = $this->playerScore($playerScore, $startdateandtime);
                    $playerScore->setScore($score);
                }
                if ($singlesMatch && $strokePlay) {
                    foreach($formbean->getPlayerScores() as $scoreBean) {
                        $game->addOrUpdatePlayerScore($scoreBean);
                    }
                } else {
                    foreach($game->getPlayermatches() as $playerMatch) {
                        foreach($formbean->getPlayerScores() as $scoreBean) {
                            if ($scoreBean->getPlayerMatch()->getId() == $playerMatch->getId()) {
                                $playerMatch->addOrUpdatePlayerScore($scoreBean);
                            }
                        }
                    }
                }
                try {
                    $game->setRecorded(true);
                    $gameRepository->saveGame($game);
                    $parameters = array('id' => $season->getId());
                    return $this->redirectToRoute('event_list', $parameters);
                } catch (Exception $e) {
                    $form->addError(new FormError('Trouble updating selected game: ' . $e->getMessage() . ' Please retry.'));
                }
            }
        }
        return $this->render('game/post.scores.html.twig',
            [
                'title' => "Post Game Scores",
                'event' => $event,
                'game' => $game,
                'gamenumber' => $gamenumber,
                'matchPlay' => $matchPlay,
                'season' => $season,
                'singlesMatch' => $singlesMatch,
                'scores' => $formbean->getPlayerScores(),
                'form' => $form->createView(),
                'viewOnly' => $viewOnly
            ]
        );
    }

	/**
	 * @param GameScoresFormBean $formbean
	 * @param EventDE $event
	 * @param GameDE $game
	 */
    private function saveGameScores(GameScoresFormBean $formbean, EventDE $event, GameDE &$game): void {
        $tee = $event->getTee();
        $startingdateandtime = $event->getStartdateandtime();
        
    }

	/**
	 * @param $event_id
	 * @param $game_id
	 * @param $gamenumber
	 *
	 * @return Response
	 */
	#[Route('/game/view/{event_id}/{game_id}/{gamenumber}', name: 'game_view', methods: ['GET'])]
	#[IsGranted('ROLE_USER')]
	public function view($event_id, $game_id, $gamenumber): Response {
        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($event_id);

		if ($event == null) {
			return $this->render('error/error.html.twig',
				['title' => 'Error', 'e' => 'There are no events that match the criteria specified.'] );
		} else {
			$singlesMatch = EventType::isSinglesMatch( $event->getEventtype() );
			$matchPlay = EventFormatType::isMatchPlay( $event->getFormat() );

			$gameRepository = new GameRepository( $this->em, $this->logger );
			$game = $gameRepository->find( $game_id );

			if ( $game == null ) {
				return $this->render( 'error/error.html.twig',
					[ 'title' => 'Error', 'e' => 'There are no games that match the criteria specified.' ] );
			} else {
				$gamenumber = 0;
				foreach ( $event->getGames() as $g ) {
					$gamenumber ++;

					if ( $g->getId() == $game->getId() ) {
						break;
					}
				}
				$season = $event->getSession()->getSeason();

				return $this->render( 'game/view.html.twig',
					[
						'title'        => 'Game',
						'game'         => $game,
						'gamenumber'   => $gamenumber,
						'event'        => $event,
						'matchPlay'    => $matchPlay,
						'season'       => $season,
						'singlesMatch' => $singlesMatch
					]
				);
			}
		}
    }
}