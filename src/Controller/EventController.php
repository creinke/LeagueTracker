<?php

namespace App\Controller;

use App\Entity\EventDE;
use App\Entity\LeagueDE;
use App\Entity\PlayerDE;
use App\Form\EventForm;
use App\Form\Type\EventType;
use App\Form\PlayersFormBean;
use App\Model\EventFormatType;
use App\Repository\CourseRepository;
use App\Repository\EventRepository;
use App\Repository\PlayerRepository;
use App\Repository\SeasonRepository;
use App\Repository\SessionRepository;
use App\View\GameResultsViewBean;
use App\View\SeasonStandingsViewBean;
use App\View\SinglesMatchPlayEventViewBean;
use App\View\SinglesMatchPlaySeasonStandingsViewBean;
use App\View\SinglesStrokePlayEventViewBean;
use App\View\SinglesStrokePlaySeasonStandingsViewBean;
use App\View\TeamEventViewBean;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use DateTime;

/**
 * Controller associated with the implementation of CRUD methods affecting Event Entities.
 */
class EventController extends AbstractController {
	private EntityManagerInterface $em;
	private LoggerInterface $logger;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		$this->em = $em;
		$this->logger = $logger;
	}

	/**
	 * Build event form
	 *
	 * @param EventForm $eventForm
	 * @param bool $edit
	 *
	 * @return FormInterface
	 */
    private function buildEventForm(EventForm $eventForm, bool $edit) : FormInterface {
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());
        
        $builder = $this->createFormBuilder($eventForm)
            ->add('event', EventType::class, ['label' => false])
            ->add('save', SubmitType::class, ['label' => 'Save Event', 'disabled' => $disableSaveButton, 'attr' => ['class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;']]);

        $form = $builder->getForm();
        
        if ($edit) {
            $elements = $form->getIterator();
            $eventForm = $elements['event'];
            $eventForm->remove('playersperteam');
            $eventForm->remove('teamsorplayerspergame');
            $eventForm->remove('withhandicapping');
            //$eventForm->remove('eventtype');
            $eventForm->remove('format');
        }
        return $form;
    }

	/**
	 * Build registration form
	 *
	 * @param LeagueDE $league
	 * @param EventDE $event
	 *
	 * @return FormInterface
	 */
    private function buildEventRegistrationForm(LeagueDE $league, EventDE $event) : FormInterface {
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
            ->add('register', SubmitType::class, array('label' => 'Register Players',
                'disabled' => $disableGenerateButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')));
                        
        $form = $builder->getForm();
        return $form;
    }


	/**
	 * @param PersistentCollection $games
	 * @param DateTime $eventDateAndTime
	 *
	 * @return PersistentCollection
	 */
	public static function changeGameTimes(PersistentCollection $games, DateTime $eventDateAndTime): PersistentCollection {
        $numberOfGames = $games->count();
        $gameDateAndTime = clone $eventDateAndTime;
        $newGameDateAndTimes = [];

        $s = $gameDateAndTime->format('Y-m-d H:i:s');

        for ($gameNumber = 0; $gameNumber < $numberOfGames; $gameNumber++) {
            $game = $games->get($gameNumber);
            $newGameDateAndTimes[] = $gameDateAndTime;

            if ($gameNumber < $numberOfGames - 1) {
                $currentGameDateAndTime = $game->getStartingtime();
                $nextGameDateAndTime = $games[$gameNumber + 1]->getStartingtime();
                $interval = $currentGameDateAndTime->diff($nextGameDateAndTime, true);
                $gameDateAndTime = clone $gameDateAndTime;
                $gameDateAndTime->add($interval);

                $s = $gameDateAndTime->format('Y-m-d H:i:s');
            }
        }
        for ($gameNumber = 0; $gameNumber < $numberOfGames; $gameNumber++) {
            $game = $games->get($gameNumber);
            $game->setStartingtime($newGameDateAndTimes[$gameNumber]);
            $games->set($gameNumber, $game);
        }
        return $games;
    }

	/**
	 * Deletes the event referenced by the $id parameter.
	 *
	 * @param Request $request
	 * @param int $id
	 *
	 * @return RedirectResponse
	 * @throws ORMException
	 * @throws Exception
	 */
	#[Route('/event/delete/{id}', name: 'event_delete', methods: ['DELETE'])]
	#[IsGranted('ROLE_ADMIN')]
	public function delete(Request $request, int $id): RedirectResponse {
        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($id);
        
        foreach ($event->getGames() as $game) {
            foreach ($game->getPlayermatches() as $playerMatch) {
                foreach($playerMatch->getPlayerscores() as $playerScore) {
                    $this->em->remove($playerScore);
                }
            }
        }
        $eventRepository->removeEvent($event);
        
        $season = $event->getSession()->getSeason();
        $parameters = array('id' => $season->getId());
        return $this->redirectToRoute('event_list', $parameters);
    }

	/**
	 * Edits the event referenced by the $id parameter.
	 *
	 * @param Request $request
	 * @param int $id
	 *
	 * @return RedirectResponse|Response
	 * @throws Exception
	 */
	#[Route('/event/edit/{id}', name: 'event_edit', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, int $id): RedirectResponse|Response {
        $user = $this->getUser();
        $league = $user->getLeague();
        $_SESSION['league'] = $league;

        $courseRepository = new CourseRepository($this->em, $this->logger);
        $courses = $courseRepository->findCoursesByLeagueId($league->getId());
        $_SESSION['courses'] = $courses;

        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($id);
        $eventType = $event->getEventtype();
        $singlesMatch = \App\Model\EventType::isSinglesMatch($event->getEventtype());
        
        $sessionRepository = new SessionRepository($this->em, $this->logger);
        $season = $event->getSession()->getSeason();
        $sessions = $sessionRepository->findSessionsBySeasonId($season->getId());
        $_SESSION['sessions'] = $sessions;

        $eventForm = new EventForm();
        $eventForm->setEvent($event);

        $form = $this->buildEventForm($eventForm, true);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $event = $form->getData()->getEvent();
                
                if (\App\Model\EventType::isTeamEvent($event->getEventtype())) {
                    $games = EventController::changeGameTimes($event->getTeamgames(), $event->getStartdateandtime());
                    $event->setTeamgames($games);
                } else {
                    $games = EventController::changeGameTimes($event->getGames(), $event->getStartdateandtime());
                    $event->setGames($games);
                }
                $event->setNineTee();

                if ($event->getSecondnine()->getId() == null) {
                    $event->setSecondnine(NULL);
                }
                $eventRepository->saveEvent($event);

                $parameters = array('id' => $season->getId());
                return $this->redirectToRoute('event_list', $parameters);
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected event: ' . $e->getMessage() . ' Please retry.'));
            }
        } 
        return $this->render('event/edit.html.twig',
            [
                'title' => "Edit Event",
                'event' => $event,
                'eventType' => $eventType,
                'singlesMatch' => $singlesMatch,
                'season' => $season,
                'form' => $form->createView()
            ]
        );
    }
    
    /**
     * @return ?EventDE EventDE
     */
    private function lastEvent() : ?EventDE {
        $user = $this->getUser();
        $league = $user->getLeague();
        $dateTime =  new DateTime();
        $dateTime->setTime(0, 0, 0, 0);
        $lastEvent = null;
        
        foreach($league->getSeasons() as $season) {
            foreach($season->getSessions() as $session) {
                foreach($session->getEvents() as $event) {
                    $eventStartTime = clone($event->getStartdateandtime());
                    $eventStartTime->setTime(0, 0, 0, 0);
                    
                    if ($eventStartTime > $dateTime) {
                        return $lastEvent;
                    } else if ($lastEvent == null || $eventStartTime > $lastEvent->getStartdateandtime()) {
                        $lastEvent = $event;
                    }
                }
            }
        }
        if (empty($lastEvent)) {
            return null;
        } else {
            return $lastEvent;
        }
    }

    /**
     * @return ?EventDE EventDE
     */
    private function nextEvent()  : ?EventDE {
        $user = $this->getUser();
        $league = $user->getLeague();
        $dateTime =  new DateTime();
        $dateTime->setTime(0, 0, 0, 0);
        $nextEvent = null;
        
        foreach($league->getSeasons() as $season) {
            foreach($season->getSessions() as $session) {
                foreach($session->getEvents() as $event) {
                    if ($event->getStartdateandtime() > $dateTime) {
                        $nextEvent = $event;
                        return $nextEvent;
                    }
                }
            }
        }
        return null;
    }
    
    /**
     * @return Response
     */

	#[Route('/event/resultslast', name: 'event_resultslast', methods: ['GET'])]
	#[IsGranted('ROLE_ADMIN')]
	public function lastEventResults(): Response {
        $event = $this->lastEvent();
        
        if ($event == null) {
            return $this->render('error/error.html.twig',
                ['title' => 'Error', 'e' => 'There are no events that match the criteria specified.'] );
        } else {
            $parameters = array('id' => $event->getId());
            return $this->redirectToRoute('event_results', $parameters);
        }
    }

	/**
	 * @param int $id
	 *
	 * @return Response
	 */

	#[Route('/event/list/{id}', name: 'event_list', methods: ['GET'])]
	#[IsGranted('ROLE_USER')]
	public function list(int $id): Response {
        $seasonRepository = new SeasonRepository($this->em, $this->logger);
        $season = $seasonRepository->findById($id);
        $leagueName = $season->getLeague()->getName();

        return $this->render('event/list.html.twig',
            array(
                'title' => 'Events',
                'league' => $leagueName,
                'season' => $season)
            );
    }

	/**
	 *
	 * @param Request $request
	 * @param int $id session id
	 *
	 * @return Response
	 * @throws Exception
	 */
	#[Route('/event/new/{id}', name: 'event_new', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_ADMIN')]
	public function new(Request $request, int $id): Response {
        $user = $this->getUser();
        $league = $user->getLeague();
        $_SESSION['league'] = $league;

        $courseRepository = new CourseRepository($this->em, $this->logger);
        $courses = $courseRepository->findCoursesByLeagueId($league->getId());
        $_SESSION['courses'] = $courses;

        $sessionRepository = new SessionRepository($this->em, $this->logger);
        $session = $sessionRepository->findById($id);
        $season = $session->getSeason();
        $sessions = $sessionRepository->findSessionsBySeasonId($season->getId());
        $_SESSION['sessions'] = $sessions;

        $event = new EventDE($this->em);
        $event->setCourse($courses[0]);
        $event->setSession($session);
        $event->setNine($event->getCourse()->getNines()[0]);
        $event->setTee($event->getNine()->getTees()[0]);

        $eventForm = new EventForm();
        $eventForm->setEvent($event);

        $form = $this->buildEventForm($eventForm, false);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData()->getEvent();
            
            if ($event->getSecondnine()->getId() == null) {
                $event->setSecondnine(NULL);
            }
            $eventRepository = new EventRepository($this->em, $this->logger);;
            $eventRepository->saveEvent($event);

            $parameters = array('id' => $season->getId());
            return $this->redirectToRoute('event_list', $parameters);
        }
        return $this->render('event/new.html.twig',
            array(
                'title' => "Add Event",
                'form' => $form->createView()));
    }

    /**
     * @param EventDE $event
     * @param PlayerDE $player
     *
     * @return boolean if the $player is registered to play in the event
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
	 * @param Request $request
	 * @param $id int id
	 *
	 * @return RedirectResponse|Response
	 * @throws Exception
	 */
	public function register(Request $request, int $id): RedirectResponse|Response {
        $user = $this->getUser();
        $league = $user->getLeague();
        
        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($id);
        $season = $event->getSession()->getSeason();
        
        $eventType = $event->getEventtype();
        $eventFormat = $event->getFormat();
        
        $form = $this->buildEventRegistrationForm($league, $event);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $players = $form->getData()->getPlayers();
            
            $registrantKeys = [];
            foreach($event->getRegistrants() as $registrant) {
                $registrantKeys[$registrant->getId()] = $registrant;
            }
            $formRegistrantKeys = [];
            foreach($players as $player) {
                $formRegistrantKeys[$player->getId()] = $player;
            }
            $playerRepository = new PlayerRepository($this->em, $this->logger);;
            
            $newRegistrants = array_diff_key($formRegistrantKeys, $registrantKeys);
            $deletedRegistrants = array_diff_key($registrantKeys, $formRegistrantKeys);
            
            foreach($deletedRegistrants as $deletedRegistrant) {
                $event->getRegistrants()->removeElement($deletedRegistrant);
            }
            if (sizeof($newRegistrants) > 0) {
                foreach($newRegistrants as $newRegistrant) {
                    $player = $playerRepository->find($newRegistrant->getId());
                    $event->getRegistrants()->add($player);
                }
            }
            $eventRepository->saveEvent($event);
            
            $parameters = array('id' => $season->getId());
            return $this->redirectToRoute('event_list', $parameters);
        }
        return $this->render('event/register.html.twig',
            [
                'title' => "Event Registration",
                'event' => $event,
                'expanded' => true,
                'multiple' => true,
                'form' => $form->createView()
            ]
        );
    }

	/**
	 * @param int $id event id
	 *
	 * @return Response
	 */
	#[Route('/event/results/{id}', name: 'event_results', methods: ['GET'])]
	#[IsGranted('ROLE_USER')]
	public function results(int $id): Response {
        $user = $this->getUser();
        $league = $user->getLeague();

        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($id);

		if ($event == null) {
			return $this->render('error/error.html.twig',
				[
					'title' => 'Event',
					'e' => "Event[$id] is undefined"
				]
			);
		}

		$eventType = $event->getEventtype();
        $eventFormat = $event->getFormat();
        
        if ($event->isTeamEvent($eventType)) {
            $noGames = $event->getTeamgames()->count() == 0;
        } else {
            $noGames = $event->getGames()->count() == 0;
        }
        if ($noGames) {
            return $this->render('error/error.html.twig',
                [
                    'title' => 'Event Results',
                    'e' => 'No Games Defined'
                ]
            );
        }
        $season = $event->getSession()->getSeason();

        if ($event->isSinglesMatch($eventType)) {
            if ($event->isStrokePlay($eventFormat)) {
                $unrecordedGames = $this->unrecordedSinglesStrokePlayGames($event);

                if (sizeof($unrecordedGames) > 0) {
                    return $this->render('event/unrecordedsinglesstrokeplayresults.html.twig',
                        [
                            'title' => 'Event Results',
                            'league' => $league,
                            'games' => $unrecordedGames,
                            'event' => $event,
                            'season' => $season
                        ]
                    );
                }
            } else {
                $unrecordedGames = $this->unrecordedSinglesMatchPlayGames($event);
                
                if (sizeof($unrecordedGames) > 0) {
                    return $this->render('event/unrecordedsinglesmatchresults.html.twig',
                        [
                            'title' => 'Event Results',
                            'league' => $league,
                            'games' => $unrecordedGames,
                            'event' => $event,
                            'season' => $season
                        ]
                    );
                }
            }
        } else if ($event->isTeamEvent($eventType)) {
            $unrecordedTeamGames = $this->unrecordedTeamGames($event);
            
            if (sizeof($unrecordedTeamGames) > 0) {
                return $this->render('event/unrecordedteamgamesresults.html.twig',
                    [
                        'title' => 'Event Results',
                        'league' => $league,
                        'teamgames' => $unrecordedTeamGames,
                        'event' => $event,
                        'season' => $season
                    ]
                );
            }
        } else {
            $unrecordedGames = $this->unrecordedTeamMatchGames($event);
            
            if (sizeof($unrecordedGames) > 0) {
                return $this->render('event/unrecordedteammatchresults.html.twig',
                    [
                        'title' => 'Event Results',
                        'league' => $league,
                        'games' => $unrecordedGames,
                        'event' => $event,
                        'season' => $season
                    ]
                );
            }
        }
        if ($eventType == \App\Model\EventType::isTeamEvent($eventType)) {
            return $this->teamEventResultsResponse($event);
        } else if ($eventType == \App\Model\EventType::isSinglesMatch($eventType)) {
            if ($eventFormat == EventFormatType::isStrokePlay($eventFormat)) {
                return $this->singlesStrokePlayResultsResponse($event);
            } else {
                return $this->singlesMatchPlayResultsResponse($event);
            }
        } else {
            return $this->teamStandingsResultsResponse($event);
        }
    }

    /**
     * @param EventDE $event
     * @return Response for Singles Match Play Event
     */
    private function singlesMatchPlayResultsResponse(EventDE $event) : Response {
        $user = $this->getUser();
        $league = $user->getLeague();
        $season = $event->getSession()->getSeason();
        $singlesMatchPlaySeasonStandingsViewBean = new SinglesMatchPlaySeasonStandingsViewBean();
        
        foreach($season->getSessions() as $session) {
            $singlesMatchPlaySeasonStandingsViewBean->setSessionPoints([]);
            
            foreach($session->getEvents() as $e) {
                if ($e->isSinglesMatch($e->getEventtype()) && $e->isMatchPlay($e->getFormat())) {
                    if (sizeof($this->unrecordedSinglesMatchPlayGames($e)) == 0) {
                        $singlesMatchPlayEventViewBean = new SinglesMatchPlayEventViewBean($event);
                        $singlesMatchPlayEventViewBean->calculateEventResults($event);
                        
                        $singlesMatchPlaySeasonStandingsViewBean->updatePlayerPoints($singlesMatchPlayEventViewBean);
                        
                        if ($e->getId() == $event->getId()) {
                            break;
                        }
                    }
                }
            }
            if ($e->getId() == $event->getId()) {
                break;
            }
        }
        $players = $singlesMatchPlayEventViewBean->array_sort($singlesMatchPlayEventViewBean->players, "sessionPoints", SORT_DESC);
        
        return $this->render('event/singlesmatchplayresults.html.twig',
            [
                'title' => 'Event Results',
                'league' => $league,
                'event' => $event,
                'players' => $players,
                'singlesMatchPlayEventViewBean' => $singlesMatchPlayEventViewBean,
                'season' => $season
            ]
        );
    }
    
    /**
     * @param EventDE $event
     * @return Response for Singles Stroke Play Event
     */
    private function singlesStrokePlayResultsResponse(EventDE $event) : Response {
        $user = $this->getUser();
        $league = $user->getLeague();
        $season = $event->getSession()->getSeason();
        $singlesStrokePlaySeasonStandingsViewBean = new SinglesStrokePlaySeasonStandingsViewBean();
        
        foreach($season->getSessions() as $session) {
            $singlesStrokePlaySeasonStandingsViewBean->setSessionPoints([]);
            
            foreach($session->getEvents() as $e) {
                if ($e->isSinglesMatch($e->getEventtype()) && $e->isStrokePlay($e->getFormat())) {
                    if (sizeof($this->unrecordedSinglesStrokePlayGames($e)) == 0) {
                        $singlesStrokePlayEventViewBean = new SinglesStrokePlayEventViewBean($e);
                        $singlesStrokePlayEventViewBean->calculateEventResults($e);
                        
                        $singlesStrokePlaySeasonStandingsViewBean->updatePlayerPoints($singlesStrokePlayEventViewBean);
                        
                        if ($e->getId() == $event->getId()) {
                            break;
                        }
                    }
                }
            }
            if ($e->getId() == $event->getId()) {
                break;
            }
        }
        $players = $singlesStrokePlayEventViewBean->array_sort($singlesStrokePlayEventViewBean->players, "sessionPoints", SORT_DESC);
        
        return $this->render('event/singlesstrokeplayresults.html.twig',
            [
                'title' => 'Event Results',
                'league' => $league,
                'event' => $event,
                'players' => $players,
                'singlesStrokePlayEventViewBean' => $singlesStrokePlayEventViewBean,
                'season' => $season
            ]
        );
    }
    
    /**
     * @param EventDE $event
     * @return Response for Team Event
     */
    private function teamEventResultsResponse(EventDE $event) : Response {
        $user = $this->getUser();
        $league = $user->getLeague();
        $season = $event->getSession()->getSeason();
        
        try {
            $teamEventViewBean = new TeamEventViewBean($event, $this->em, $this->logger);
            $teamEventViewBean->calculateEventResults($event);
        } catch (Exception $e) {
            return $this->render('error/error.html.twig',
                ['title' => 'Error', 'e' => $e->getMessage()] );
        }
        $template = $teamEventViewBean->isScramble ? 'event/teameventscrambleresults.html.twig' : 'event/teameventresults.html.twig';
        
        return $this->render($template,
            [
                'title' => 'Event Results',
                'league' => $league,
                'event' => $event,
                'teamEventViewBean' => $teamEventViewBean,
                'season' => $season
            ]
        );
    }
        
    /**
     * @param EventDE $event
     * @return Response for Team Standings Event 
     */
    private function teamStandingsResultsResponse(EventDE $event) : Response {
        $user = $this->getUser();
        $league = $user->getLeague();        
        $season = $event->getSession()->getSeason();
        $seasonStandingsViewBean = new SeasonStandingsViewBean($season);
        
        foreach($season->getSessions() as $session) {
            foreach($session->getEvents() as $e) {
                if ($e->isTeamMatch($e->getEventtype())) {
                    if (sizeof($this->unrecordedTeamMatchGames($e)) == 0) {
                        $gameResultsViewBean = new GameResultsViewBean($e);
                        $seasonStandingsViewBean->updateTeamStandingsViewBeans($e, $session->getName(), $gameResultsViewBean);
                        
                        if ($e->getId() == $event->getId()) {
                            break;
                        }
                    }
                }
            }
            if ($e->getId() == $event->getId()) {
                break;
            }
        }
        foreach($season->getSessions() as $session) {
            $seasonStandingsViewBean->sortSessionTeamStandings($session->getName());
        }
        $seasonStandingsViewBean->sortSeasonTeamStandings();
        
        return $this->render('event/teammatchresults.html.twig',
            [
                'title' => 'Event Results',
                'league' => $league,
                'event' => $event,
                'gameResultsViewBean' => $gameResultsViewBean,
                'seasonStandingsViewBean' => $seasonStandingsViewBean,
                'season' => $season
            ]
        );
    }
    
    /**
     * @param EventDE $event
     * @return array of \App\Entity\GameDE
     */
    private function unrecordedSinglesMatchPlayGames(EventDE $event) : array {
        $unrecordedGames = [];
        
        foreach($event->getGames() as $game) {
            if (!$game->isRecorded()) {
                $unrecordedGames[] = $game;
            }
        }
        return $unrecordedGames;
    }
    
    /**
     * @param EventDE $event
     * @return array of \App\Entity\GameDE 
     */
    private function unrecordedSinglesStrokePlayGames(EventDE $event) : array {
        $unrecordedGames = [];
        
        foreach($event->getGames() as $game) {
            if (!$game->isRecorded()) {
                $unrecordedGames[] = $game;
            }
        }
        return $unrecordedGames;
    }
    
    /**
     * @param EventDE $event
     * @return array of \App\Entity\GameDE
     */
    private function unrecordedTeamMatchGames(EventDE $event) : array {
        $unrecordedGames = [];
        
        foreach($event->getGames() as $game) {
            if (!$game->isRecorded()) {
                $unrecordedGames[] = $game;
            }
        }
        return $unrecordedGames;
    }
    
    /**
     * @param EventDE $event
     * @return array of \App\Entity\TeamgameDE 
     */
    private function unrecordedTeamGames(EventDE $event) : array {
        $unrecordedTeamGames = [];
        
        foreach($event->getTeamgames() as $teamGame) {
            if (!$teamGame->isRecorded()) {
                $unrecordedTeamGames[] = $teamGame;
            }
        }
        return $unrecordedTeamGames;
    }

	/**
	 * @param $id
	 *
	 * @return Response
	 */
	#[Route('/event/view/{id}', name: 'event_view', methods: ['GET'])]
	#[IsGranted('ROLE_USER')]
    public function view($id): Response {
        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($id);

		if ($event == null) {
			return $this->render('error/error.html.twig',
				[
					'title' => 'Event',
					'e' => "Event[$id] is undefined"
				]
			);
		}
        $season = $event->getSession()->getSeason();
        $singlesMatch = \App\Model\EventType::isSinglesMatch($event->getEventtype());
        
        if (EventFormatType::isMatchPlay($event->getFormat())) {
            $matchPlay = true;
        } else {
            $matchPlay = false;
        }
        return $this->render('event/view.html.twig',
            [
                'title' => 'Event',
                'event' => $event,
                'matchPlay' => $matchPlay,
                'season' => $season,
                'singlesMatch' => $singlesMatch
            ]
        );
    }


	/**
	 * @return RedirectResponse|Response
	 */
	#[Route('/event/viewlast', name: 'event_viewlast', methods: ['GET'])]
	#[IsGranted('ROLE_USER')]
	public function viewLastEvent(): RedirectResponse|Response {
        $event = $this->lastEvent();
        
        if ($event == null) {
            return $this->render('error/error.html.twig', 
                ['title' => 'Error', 'e' => 'There are no events that match the criteria specified.'] );
        } else {
            $parameters = array('id' => $event->getId());
            return $this->redirectToRoute('event_view', $parameters);
        }
    }

	/**
	 * @return Response
	 */
	#[Route('/event/viewnext', name: 'event_viewnext', methods: ['GET'])]
	#[IsGranted('ROLE_USER')]
	public function viewNextEvent(): Response {
        $event = $this->nextEvent();
        
        if ($event == null) {
            return $this->render('error/error.html.twig',
                    ['title' => 'Error', 'e' => 'There are no events that match the criteria specified.'] );
        } else {
            $parameters = array('id' => $event->getId());
            return $this->redirectToRoute('event_view', $parameters);
        }
    }

	/**
	 * @return RedirectResponse|Response
	 */
	#[Route('/event/viewseason', name: 'event_viewseason', methods: ['GET'])]
	#[IsGranted('ROLE_USER')]
    public function viewSeasonEvents(): RedirectResponse|Response {
        $event = $this->lastEvent();
        
        if ($event == null) {
            return $this->render('error/error.html.twig',
                ['title' => 'Error', 'e' => 'There are no events that match the criteria specified.'] );
        } else {
            $parameters = array('id' => $event->getSession()->getSeason()->getId());
            return $this->redirectToRoute('event_list', $parameters);
        }
    }
}