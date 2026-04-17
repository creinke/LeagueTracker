<?php

namespace App\Controller;

use App\Entity\CourseDE;
use App\Entity\EventDE;
use App\Entity\GameDE;
use App\Entity\LeagueDE;
use App\Entity\NineDE;
use App\Entity\PlayerDE;
use App\Entity\PlayermatchDE;
use App\Entity\SeasonDE;
use App\Entity\SessionDE;
use App\Entity\TeeDE;
use App\Entity\TeammatchDE;
use App\Form\CreateScheduleFormBean;
use App\Form\SeasonForm;
use App\Form\Type\SeasonType;
use App\Model\EventType;
use App\Model\EventFormatType;
use App\Model\GameFormatType;
use App\Repository\CourseRepository;
use App\Repository\EventRepository;
use App\Repository\LeagueRepository;
use App\Repository\PlayerRepository;
use App\Repository\PlayermatchRepository;
use App\Repository\SeasonRepository;
use App\Repository\SessionRepository;
use App\Repository\ScoreRepository;
use App\Repository\TeammatchRepository;
use App\Repository\TeamRepository;
use App\Utility\Pairings;
use DateMalformedStringException;
use Doctrine\ORM\EntityManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SeasonController extends AbstractController {
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Build create a schedule form
     *
     * @param CreateScheduleFormBean $view
     * @param LeagueDE $league
     *
     * @return FormInterface
     */
    private function buildCreateScheduleForm(CreateScheduleFormBean $view, LeagueDE $league) : FormInterface {
        $course = $league->getCourses()[0];
        $nines = $course->getNines();
        $tees = $nines[0]->getTees();

        $eventTypeChoices = array();
        $eventTypes = EventType::values();
        foreach($eventTypes as $eventType => $eventTypeValue) {
            if ($eventTypeValue == EventType::LEAGUE_MATCH || $eventTypeValue == EventType::SINGLES_MATCH) {
                $eventTypeChoices[$eventTypeValue] = $eventType;
            }
        }
        $eventFormatChoices = array();
        $eventFormats = EventFormatType::values();
        foreach($eventFormats as $eventFormat => $eventFormatValue) {
            $eventFormatChoices[$eventFormatValue] = $eventFormat;
        }
        $teeChoices = array();
        foreach($tees as $tee) {
            $key = $tee->getName();
            $teeChoices[$key] = $tee->getId();
        }
        $nineChoices = array();
        foreach($nines as $nine) {
            $key = $nine->getName();
            $nineChoices[$key] = $nine->getId();
        }
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());
        
        $builder = $this->createFormBuilder($view)
            ->add('seasonName', TextType::class, array('label' => 'Season Name, e.g., 2018, fall 2018, etc.', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('eventType', ChoiceType::class,
                ['label' => 'Event Type', 'required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'], 'choices'  => $eventTypeChoices])
            ->add('eventFormat', ChoiceType::class,
                ['label' => 'Event Format', 'required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'], 'choices'  => $eventFormatChoices])
            ->add('withhandicapping', CheckboxType::class,
                ['label' => 'With Handicapping (Checked = true)', 'required' => false, 'attr' => ['style' => 'width: 10%;', 'class' => 'form-control']])
            ->add('numberOfWeeks', NumberType::class, array('label' => 'Number of Weeks in Season', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('numberOfSessions', NumberType::class, array('label' => 'Number of Sessions in Season', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('playersPerTeam', NumberType::class, array('label' => 'Number of Players per Team', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('teamsOrPlayersPerGame', NumberType::class, array('label' => 'Number of Teams or Players per Game', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('seasonStartingDateAndTime', DateTimeType::class,
                ['label' => 'Starting Date and Time (MM/dd/yyyy, hh:mm a)', 'widget' => 'single_text', 'required' => true, 'attr' => ['class' => 'form-control']])
            ->add('minutesBetweenGames', NumberType::class,
                ['label' => 'Minutes Between Games', 'required' => true, 'attr' => ['class' => 'form-control']])
            ->add('startingNine', ChoiceType::class, array('label' => 'Starting Nine', 'required' => true, 'attr' => array('class' => 'form-control', 'style' => 'height: 45px;'),
                'choices'  => $nineChoices))
            ->add('tee', ChoiceType::class, array('label' => 'Tee', 'required' => true, 'attr' => array('class' => 'form-control', 'style' => 'height: 45px;'),
                'choices'  => $teeChoices))
            ->add('generate', SubmitType::class, array('label' => 'Generate', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em; margin-bottom: 2em;')));

        return $builder->getForm();
    }

    /**
     * Build new season form
     *
     * @param SeasonDE $season
     * @return FormInterface
     */
    private function buildNewSeasonForm(SeasonDE $season) : FormInterface {
        /** @noinspection DuplicatedCode */
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $form = $this->createFormBuilder($season)
            ->add('name', TextType::class, array('label' => 'Name', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('startdate', DateType::class, array('label' => 'Start Date', 'required' => true, 'widget' => 'single_text', 'attr' => array('class' => 'form-control')))
            ->add('enddate', DateType::class, array('label' => 'End Date', 'required' => true, 'widget' => 'single_text', 'attr' => array('class' => 'form-control')))
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')))
            ->getForm()
        ;

        return $form;
    }


    /**
     * @param SeasonForm $seasonForm
     *
     * @return FormInterface
     */
    private function buildSeasonForm(SeasonForm $seasonForm) : FormInterface {
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());
        $options = [];

        $builder = $this->createFormBuilder($seasonForm, $options)
            ->add('season', SeasonType::class, ['label' => false])
            ->add('save', SubmitType::class, ['label' => 'Save Season', 'disabled' => $disableSaveButton, 'attr' => ['class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;']]);

        $form = $builder->getForm();
        $elements = $form->getIterator();
        $seasonForm = $elements['season'];
        $seasonElements = $seasonForm->getIterator();
        $sessionForm = $seasonElements['sessions'];
        $sessionsElements = $sessionForm->getIterator();
        
        foreach($sessionsElements as $sessionElement) {
            $eventsElements = $sessionElement['events'];
            
            foreach($eventsElements as $eventElement) {
                $eventElement->remove('playersperteam');
                $eventElement->remove('minutesbetweengames');
                $eventElement->remove('teamsorplayerspergame');
                $eventElement->remove('withhandicapping');
                $eventElement->remove('mixedteesenabled');
                $eventElement->remove('eventtype');
                $eventElement->remove('format');
            }
        }
        return $form;
    }

    /**
     * @param int $eventNumber
     * @param int $eventType
     * @param int $eventFormat
     * @param int $playersPerTeam
     * @param int $teamsOrPlayersPerGame
     * @param int $gameFormat
     * @param SessionDE $session
     * @param NineDE $nine
     * @param TeeDE $tee
     * @param DateTime $eventDateAndTime
     * @param int $minutesBetweenGames
     * @param array $pairings
     *
     * @return EventDE
     * @throws DateMalformedStringException
     */
    private function createNewEvent(int $eventNumber, int $eventType, int $eventFormat, int $playersPerTeam, int $teamsOrPlayersPerGame, int $gameFormat, SessionDE &$session, NineDE $nine, TeeDE $tee, DateTime $eventDateAndTime, int $minutesBetweenGames, array $pairings) : EventDE {
        $event = new EventDE();
        $event->setEventnumber($eventNumber);
        $event->setEventtype($eventType);
        $event->setPlayersperteam($playersPerTeam);
        $event->setTeamsorplayerspergame($teamsOrPlayersPerGame);
        $event->setFormat($eventFormat);
        $event->setSession($session);
        $event->setCourse($nine->getCourse());
        $event->setNine($nine);
        $event->setTee($tee);
        $event->setStartdateandtime($eventDateAndTime);
        $event->setMinutesbetweengames($minutesBetweenGames);

        $teeDateAndTime = clone $eventDateAndTime;
        
        for ($gameNumber = 0; $gameNumber < sizeof($pairings); $gameNumber++) {
            $gameDateAndTime = clone $teeDateAndTime;
            
            if (EventType::isTeamMatch($eventType)) {
                $game = SeasonController::createNewTeamMatchGame(new GameDE(), $event, $this->em, $this->logger, $pairings[$gameNumber], $gameDateAndTime, $gameFormat);
            } else {
                if (EventFormatType::isStrokePlay($eventFormat)) {
                    $game = SeasonController::createNewSinglesGame(new GameDE(), $event, $this->em, $this->logger, $pairings[$gameNumber], $gameDateAndTime, $gameFormat);
                } else {
                    $game = SeasonController::createNewSinglesTeamMatchGame(new GameDE(), $event, $this->em, $this->logger, $pairings[$gameNumber], $gameDateAndTime, $gameFormat);
                }
            }
            $event->getGames()->add($game);
            $teeDateAndTime->modify('+' . $minutesBetweenGames . ' minutes');
        }
        return $event;
    }

    /**
     * @param GameDE $game
     * @param EventDE $event
     * @param EntityManager $em
     * @param LoggerInterface $logger
     * @param array $pairings
     * @param DateTime $gameDateAndTime
     * @param int $gameFormat
     *
     * @return GameDE
     */
    public static function createNewSinglesGame(GameDE $game, EventDE $event, EntityManagerInterface $em, LoggerInterface $logger, array $pairings, DateTime $gameDateAndTime, int $gameFormat): GameDE {
        $game->setFormat($gameFormat);
        $game->setStartingtime($gameDateAndTime);
        $game->setEvent($event);
        
        $playerRepository = new PlayerRepository($em, $logger);
        
        foreach($pairings as $p) {
            $player = $playerRepository->find($p->getId());
            $game->getPlayers()->add($player);
        }
        return $game;
    }

    /**
     * @param GameDE $game
     * @param EventDE $event
     * @param EntityManager $em
     * @param LoggerInterface $logger
     * @param array $pairings
     * @param DateTime $gameDateAndTime
     * @param int $gameFormat
     *
     * @return GameDE
     */
    public static function createNewSinglesTeamMatchGame(GameDE $game, EventDE $event, EntityManagerInterface $em, LoggerInterface $logger, array $pairings, DateTime $gameDateAndTime, int $gameFormat): GameDE {
        $game->setFormat($gameFormat);
        $game->setStartingtime($gameDateAndTime);
        $game->setEvent($event);
        
        $playerRepository = new PlayerRepository($em, $logger);
        
        $teamOnePlayers = $pairings[0];
        $teamTwoPlayers = $pairings[1];
        $playersInGame = sizeof($teamOnePlayers);
        
        for ($playerIndex = 0; $playerIndex < $playersInGame; $playerIndex++) {
            $playerOne = $playerRepository->find($teamOnePlayers[$playerIndex]->getId());
            $playerTwo = $playerRepository->find($teamTwoPlayers[$playerIndex]->getId());
            
            $playerMatch = new PlayermatchDE();
            $playerMatch->setGame($game);
            $playerMatch->setPlayerone($playerOne);
            $playerMatch->setPlayertwo($playerTwo);
            
            $game->getPlayermatches()->add($playerMatch);
        }
        return $game;
    }

    /**
     * @param GameDE $game
     * @param EventDE $event
     * @param EntityManager $em
     * @param LoggerInterface $logger
     * @param array $pairings
     * @param DateTime $gameDateAndTime
     * @param int $gameFormat
     *
     * @return GameDE
     */
    public static function createNewTeamMatchGame(GameDE $game, EventDE $event, EntityManagerInterface $em, LoggerInterface $logger, array $pairings, DateTime $gameDateAndTime, int $gameFormat): GameDE {
        $game->setFormat($gameFormat);
        $game->setStartingtime($gameDateAndTime);
        $game->setEvent($event);
        
        $teamMatch = new TeammatchDE();
        $teamMatch->setGame($game);
        
        $teamOne = $pairings[0];
        $teamTwo = $pairings[1];
        $teamMatch->setTeamone($teamOne);
        $teamMatch->setTeamtwo($teamTwo);
        $game->getTeammatches()->add($teamMatch);
        
        $playerRepository = new PlayerRepository($em, $logger);

        /** @noinspection DuplicatedCode */
        for ($playerIndex = 0; $playerIndex < $teamOne->getPlayers()->count(); $playerIndex++) {
            $playerOne = $playerRepository->find($teamOne->getPlayers()->get($playerIndex)->getId());
            $playerTwo = $playerRepository->find($teamTwo->getPlayers()->get($playerIndex)->getId());
            
            $playerMatch = new PlayermatchDE();
            $playerMatch->setGame($game);
            $playerMatch->setPlayerone($playerOne);
            $playerMatch->setPlayertwo($playerTwo);
            
            $game->getPlayermatches()->add($playerMatch);
        }
        return $game;
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return RedirectResponse
     * @throws Exception
     */
    #[Route('/season/delete/{id}', name: 'season_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, $id): RedirectResponse {
        $seasonRepository = new SeasonRepository($this->em, $this->logger);
        $season = $seasonRepository->find($id);
        $seasonRepository->removeSeason($season);

        $league = $season->getLeague();
        $leagueId = $league->getId();
        $playerRepository = new PlayerRepository($this->em, $this->logger);
        $playermatchRepository = new PlayermatchRepository($this->em, $this->logger);
        $teamRepository = new TeamRepository($this->em, $this->logger);
        $teammatchRepository = new TeammatchRepository($this->em, $this->logger);
        $scoreRepository = new ScoreRepository($this->em, $this->logger);
        
        $playerResult = $playerRepository->findAllPlayers($leagueId);

        foreach ($playerResult as $o) {
            if ($o instanceof PlayerDE) {
                $player = $o;
                
                if ($player->isDefunct()) {
                    $playermatch = $playermatchRepository->findOneByPlayerId($player->getId());
                    
                    if (empty($playermatch)) {
                        $scores = $scoreRepository->findPlayerScores($player, new DateTime());

                        foreach($scores as $score) {
                            $scoreRepository->removeScore($score);
                        }
                        $playerRepository->removePlayer($player);
                    } 
                }
            }
        }
        $teams = $teamRepository->findAllTeams($leagueId);
        
        foreach ($teams as $team) {
            if ($team->isDefunct()) {
                $teammatch = $teammatchRepository->findOneByTeamId($team->getId());
                
                if (empty($teammatch)) {
                    $teamRepository->removeTeam($team);
                } 
            }
        }
        return $this->redirectToRoute('season_list');
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */
    #[Route('/season/edit/{id}', name: 'season_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, $id): RedirectResponse|Response {
        $user = $this->getUser();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $user->getLeague();
        $_SESSION['league'] = $league;

        $courseRepository = new CourseRepository($this->em, $this->logger);
        $courses = $courseRepository->findCoursesByLeagueId($league->getId());
        $_SESSION['courses'] = $courses;

        $seasonRepository = new SeasonRepository($this->em, $this->logger);
        $season = $seasonRepository->find($id);

        $sessionRepository = new SessionRepository($this->em, $this->logger);
        $sessions = $sessionRepository->findSessionsBySeasonId($season->getId());
        $_SESSION['sessions'] = $sessions;

        $seasonForm = new SeasonForm();
        $seasonForm->setSeason($season);

        $form = $this->buildSeasonForm($seasonForm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $season = $form->getData()->getSeason();
                $sessions = $season->getSessions();

                foreach($sessions as $session) {
                    $events = $session->getEvents();

                    foreach($events as $event) {
                        $games = EventController::changeGameTimes($event->getGames(), $event->getStartdateandtime());
                        $event->setGames($games);
                        $event->setNineTee();
                        
                        if ($event->getSecondnine()->getId() == null) {
                            $event->setSecondnine(NULL);
                        }
                    }
                }
                $seasonRepository->saveSeason($season);

                return $this->redirectToRoute('season_list');
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected season: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        return $this->render('season/edit.html.twig',
            array(
                'title' => "Edit Season",
                'season' => $season,
                'seasonName' => $season->getName(),
                'form' => $form->createView()));
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    #[Route('/season/generate', name: 'season_generate', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function generate(Request $request): RedirectResponse|Response {
        $user = $this->getUser();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $user->getLeague();
        $leagueId = $league->getId();

        $view = new CreateScheduleFormBean();
        $form = $this->buildCreateScheduleForm($view, $league);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $leagueRepository = new LeagueRepository($this->em, $this->logger);
                $league = $leagueRepository->findById($leagueId);

                $season = $this->generateSeason($view, $league);
                $league->getSeasons()->count();
                $league->getSeasons()->add($season);

                $leagueRepository->saveLeague($league);
                return $this->redirectToRoute('season_list');
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble generating season: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        return $this->render('season/generate.html.twig',
            array(
                'title' => "Generate Season",
                'form' => $form->createView()));
    }

    /**
     * @param CreateScheduleFormBean $view
     * @param LeagueDE $league
     *
     * @return SeasonDE
     * @throws Exception
     */
    private function generateSeason(CreateScheduleFormBean $view, LeagueDE $league) : SeasonDE {
        $courseRepository = new CourseRepository($this->em, $this->logger);
        $course = $courseRepository->find($league->getCourses()[0]->getId());
        $nines = $course->getNines()->toArray();
        $nine = $this->findNineById($course, $view->getStartingNine());
        $nextNine = $nine;
        $tee = $this->findTeeById($nines, $view->getTee());
        $teeName = $tee->getName();
        $eventDateAndTime = clone $view->getSeasonStartingDateAndTime();
        $eventType = $view->getEventType();
        $eventFormat = $view->getEventFormat();

        /** @noinspection PhpCastIsUnnecessaryInspection */
        $numberOfSessions = intval($view->getNumberOfSessions());
        $numberOfWeeks = $view->getNumberOfWeeks();
        $playersPerTeam = $view->getPlayersPerTeam();
        $teamsOrPlayersPerGame = $view->getTeamsOrPlayersPerGame();
        $numberOfPlayoffEvents = $numberOfSessions == 2 ? 1 : 0;
        
        if (EventFormatType::isStrokePlay($eventFormat)) {
            $numberOfPositionEventsPerSession = 0;
            $numberOfEvents = $numberOfWeeks;
        } else {
            $numberOfPositionEventsPerSession = 1;
            $numberOfEvents = $numberOfWeeks - ($numberOfPositionEventsPerSession * $numberOfSessions) - $numberOfPlayoffEvents;
        }
        $numberOfEventsPerSession = intval($numberOfEvents / $numberOfSessions);
        
        $season = new SeasonDE();
        $season->setName($view->getSeasonName());
        $season->setStartdate(clone $eventDateAndTime);
        $season->setLeague($league);

        $pairings = [];
        if (EventType::isTeamMatch($eventType)) {
            $gameFormat = GameFormatType::toOrdinal(GameFormatType::SINGLES_MATCH_PLAY);
            $teams = $league->getCurrentlyActiveTeams();
            $pairings = Pairings::generateTeanMatchPairings($teams, $numberOfEvents);
        } else {
            $players = [];
            $playerRepository = new PlayerRepository($this->em, $this->logger);
            $p = $playerRepository->findAllPlayers($league->getId());
            
            foreach ($p as $o) {
                if ($o instanceof PlayerDE && $o->getType() == 'REGULAR' && $o->getLastname() != "Course") {
                    $players[] = $o;
                }
            }
            if (EventFormatType::isStrokePlay($eventFormat)) {
                $gameFormat = GameFormatType::toOrdinal(GameFormatType::SINGLES_STROKE_PLAY);
                $pairings = Pairings::generateRandomSinglesPairings($players, $numberOfEvents, $teamsOrPlayersPerGame);
            } else {
                $gameFormat = GameFormatType::toOrdinal(GameFormatType::SINGLES_MATCH_PLAY);
                $pairings = Pairings::generateRandomTeamPairings($players, $numberOfEvents, $teamsOrPlayersPerGame);
            }
        }
        $currentPairingOffset = -1;

        for ($sessionNumber = 0; $sessionNumber < $numberOfSessions; $sessionNumber++) {
            $session = new SessionDE();
            $session->setStartdate(clone $eventDateAndTime);
            $session->setSeason($season);

            if ($numberOfSessions == 2) {
                if ($sessionNumber == 0) {
                    $session->setName('1st half');
                } else {
                    $session->setName('2nd half');
                }
            } else {
                if ($sessionNumber == 2) {
                    $session->setName('2nd session');
                } else if ($sessionNumber == 3) {
                    $session->setName('3rd session');
                } else {
                    $session->setName($sessionNumber + 1 . 'st session');
                }
            }
            for ($eventNumber = 0; $eventNumber < $numberOfEventsPerSession; $eventNumber++) {
                if (++$currentPairingOffset == sizeof($pairings)) {
                    $currentPairingOffset = 0;
                }
                /** @noinspection DuplicatedCode */
                $tee = $this->findTeeByName($nextNine, $teeName);
                $event = $this->createNewEvent($eventNumber + 1, $eventType, $eventFormat, $playersPerTeam, $teamsOrPlayersPerGame, $gameFormat, $session, $nextNine, $tee, clone $eventDateAndTime, $view->getMinutesBetweenGames(), $pairings[$currentPairingOffset]);
                $event->setSession($session);
                $session->getEvents()->add($event);

                $eventDateAndTime->modify('+7 day');
                $nextNine = $this->nextNine($nines, $nextNine);
            }
            for (; $eventNumber < $numberOfEventsPerSession + $numberOfPositionEventsPerSession; $eventNumber++) {
                /** @noinspection DuplicatedCode */
                $type = EventType::toOrdinal(EventType::POSITION_MATCH);
                $tee = $this->findTeeByName($nextNine, $teeName);
                $event = $this->createNewEvent($eventNumber + 1, $type, $eventFormat, $playersPerTeam, $teamsOrPlayersPerGame, $gameFormat, $session, $nextNine, $tee, clone $eventDateAndTime, $view->getMinutesBetweenGames(), $pairings[$currentPairingOffset]);
                $event->setSession($session);
                $session->getEvents()->add($event);

                $eventDateAndTime->modify('+7 day');
                $nextNine = $this->nextNine($nines, $nextNine);
            }
            if ($numberOfSessions == 2 && $sessionNumber == 1) {
                for (; $eventNumber < $numberOfEventsPerSession + $numberOfPositionEventsPerSession + $numberOfPlayoffEvents; $eventNumber++) {
                    /** @noinspection DuplicatedCode */
                    $type = EventType::toOrdinal(EventType::PLAYOFF_MATCH);
                    $tee = $this->findTeeByName($nextNine, $teeName);
                    $event = $this->createNewEvent($eventNumber + 1, $type, $eventFormat, $playersPerTeam, $teamsOrPlayersPerGame, $gameFormat, $session, $nextNine, $tee, clone $eventDateAndTime, $view->getMinutesBetweenGames(), $pairings[$currentPairingOffset]);
                    $event->setSession($session);
                    $session->getEvents()->add($event);

                    $eventDateAndTime->modify('+7 day');
                    $nextNine = $this->nextNine($nines, $nextNine);
                }
            }
            $sessionEndDateAndTime = clone $eventDateAndTime;
            $sessionEndDateAndTime->modify('-7 day');
            $session->setEnddate(clone $sessionEndDateAndTime);
            $season->getSessions()->add($session);
        }
        /** @noinspection PhpUndefinedVariableInspection */
        $season->setEnddate(clone $sessionEndDateAndTime);
        return $season;
    }

    /**
     * @param CourseDE $course
     * @param int $id
     * @return NineDE|null
     */
    private function findNineById(CourseDE $course, int $id) : ?NineDE {
        foreach($course->getNines() as $nine) {
            if ($nine->getId() == $id) {
                return $nine;
            }
        }
        return null;
    }

    /**
     * @param int $id
     *
     * @return object|null
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function findEvent(int $id) : ?EventDE {
        $eventRepository = new EventRepository($this->em, $this->logger);
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $e = $eventRepository->find($id);
        return $e;
    }
    
    /**
     * @param array $nines of NineDE $nines
     * @param int $id of the TeeDE
     * @return TeeDE|null
     */
    private function findTeeById(array $nines, int $id) : ?TeeDE {
        foreach($nines as $nine) {
            foreach($nine->getTees() as $tee) {
                if ($tee->getId() == $id) {
                    return $tee;
                }
            }
        }
        return null;
    }
    
    /**
     * @param NineDE $nine
     * @param string $name of tee
     * @return TeeDE|null
     */
    private function findTeeByName(NineDE $nine, string $name) : ?TeeDE {
        foreach($nine->getTees() as $tee) {
            if ($tee->getName() == $name) {
                return $tee;
            }
        }
        return null;
    }

    /**
     * @return Response
     * @throws Exception
     */
    #[Route('/season/list', name: 'season_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): Response {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $this->getUser()->getLeague();
        $leagueId = $league->getId();
        $leagueName = $league->getName();

        $seasonRepository = new SeasonRepository($this->em, $this->logger);
        $seasons = $seasonRepository->findSeasonsByLeagueId($leagueId);

        return $this->render('season/list.html.twig',
            array(
                'title' => 'Seasons',
                'league' => $leagueName,
                'seasons' => $seasons)
            );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/season/new', name: 'season_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response {
        $season = new SeasonDE();

        $form = $this-> buildNewSeasonForm($season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $season = $form->getData();

            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $league = $this->getUser()->getLeague();
            $leagueId = $league->getId();
            $league = new LeagueRepository($this->em, $this->logger);
            $league = $league->findById($leagueId);

            $season->setLeague($league);

            $seasonRepository = new SeasonRepository($this->em, $this->logger);
            $seasonRepository->saveSeason($season);

            return $this->redirectToRoute('season_list');
        }
        return $this->render('season/new.html.twig',
            array(
                'title' => "Add Season",
                'form' => $form->createView()));
    }

    /**
     * @param array $nines
     * @param NineDE $currentNine
     * @return NineDE
     */
    private function nextNine(array $nines, NineDE $currentNine): NineDE {
        for ($i = 0; $i < count($nines); $i++) {
            $nine = $nines[$i];

            if ($nine->getId() == $currentNine->getId()) {
                $i++;
                break;
            }
        }
        if ($i == count($nines)) {
            return $nines[0];
        } else {
            return $nines[$i];
        }
    }

    /**
     * @param $id
     *
     * @return Response
     */
    #[Route('/season/view/{id}', name: 'season_view', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function view($id): Response {
        $seasonRepository = new SeasonRepository($this->em, $this->logger);
        $season = $seasonRepository->find($id);

        if ($season == null) {
            return $this->render('error/error.html.twig',
                ['title' => 'Error', 'e' => 'There are no seasons that match the criteria specified.'] );
        } else {
            return $this->render('season/view.html.twig',
                array(
                    'title' => 'Season',
                    'season' => $season)
            );
        }
    }
}