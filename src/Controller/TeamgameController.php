<?php

namespace App\Controller;

use App\Entity\EventDE;
use App\Entity\GameDE;
use App\Entity\LeagueDE;
use App\Entity\PlayerDE;
use App\Entity\ScoreDE;
use App\Entity\TeamgameDE;
use App\Entity\TeamgameplayerDE;
use App\Form\GameScoresFormBean;
use App\Form\ScoreBean;
use App\Form\Type\TeamgameplayerScoreType;
use App\Repository\EventRepository;
use App\Repository\PlayerRepository;
use App\Repository\ScoreRepository;
use App\Repository\TeamgameRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Form\Type\TeamScoreType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TeamgameController extends AbstractController {
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Build game scores form
     *
     * @param EventDE $event
     * @param TeamgameDE $teamgame
     *
     * @return FormInterface
     */
    private function buildTeamGameScoresForm(EventDE $event, TeamgameDE $teamgame) : FormInterface {
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());
        $builder = $this->createFormBuilder($teamgame);

        if ($event->isScramble($event->getFormat())) {
            $builder
                ->add('teamFormScoreBeanCollection', CollectionType::class, array('entry_type' => TeamScoreType::class, 'entry_options' => array('attr' => array('style' => 'height: 2.5em; width: 2.7em; color: black;')),'required' => true));
        } else {
            $builder
                ->add('teamOnePlayersCollection', CollectionType::class, array('entry_type' => TeamgameplayerScoreType::class, 'entry_options' => array('attr' => array('style' => 'height: 2.5em; width: 2.7em; color: black;')),'required' => true));
        
            if (!empty($teamgame->getTeamtwo())) {
                $builder
                    ->add('teamTwoPlayersCollection', CollectionType::class, array('entry_type' => TeamgameplayerScoreType::class, 'entry_options' => array('attr' => array('style' => 'height: 2.5em; width: 2.7em; color: black;')),'required' => true));
               }
        }
        $builder
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')));

        $form = $builder->getForm();
        
        if (empty($event->getSecondnine())) {
            $elements = $form->getIterator();
            
            if ($event->isScramble($event->getFormat())) {
                $teamScoreTypeForm = $elements['teamFormScoreBeanCollection'];
                $teamScoreTypeElements = $teamScoreTypeForm->getIterator();
            
                foreach($teamScoreTypeElements as $teamScoreTypeElement) {
                    $teamScoreTypeElement->remove('secondnine');
                }
            } else {
                $teamPlayerScoreTypeForm = $elements['teamOnePlayersCollection'];
                $teamPlayerScoreTypeElements = $teamPlayerScoreTypeForm->getIterator();
                
                foreach($teamPlayerScoreTypeElements as $teamPlayerScoreTypeElement) {
                    $teamPlayerScoreTypeElement->remove('secondnine');
                }
                if (!empty($teamgame->getTeamtwo())) {
                    $teamPlayerScoreTypeForm = $elements['teamTwoPlayersCollection'];
                    $teamPlayerScoreTypeElements = $teamPlayerScoreTypeForm->getIterator();
                    
                    foreach($teamPlayerScoreTypeElements as $teamPlayerScoreTypeElement) {
                        $teamPlayerScoreTypeElement->remove('secondnine');
                    }
                }
            }
        }
        return $form;
    }

    /**
     * Build team game form
     *
     * @param TeamgameDE $teamgame
     * @param LeagueDE $league
     * @param EventDE $event
     * @return FormInterface
     */
    private function buildTeamgameForm(TeamgameDE $teamgame, LeagueDE $league, EventDE $event) : FormInterface {
        $playerChoices = array();
        $playerChoices[' '] = null;
        
        foreach($league->getPlayers() as $player) {
            $playerChoices[$player->getName()->getFullname()] = $player->getId();
        }
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());
        
        $newTeamOnePlayers = ($event->getPlayersperteam() - $teamgame->getTeamOnePlayersCollection()->count());
        while ($newTeamOnePlayers-- > 0) {
            $teamgame->getPlayers()->add(new TeamgameplayerDE(new PlayerDE(), 1));
        }
        
        if ($event->getTeamsorplayerspergame() > 1) {
            $newTeamTwoPlayers = ($event->getPlayersperteam() - $teamgame->getTeamTwoPlayersCollection()->count());
            while ($newTeamTwoPlayers-- > 0) {
                $teamgame->getPlayers()->add(new TeamgameplayerDE(new PlayerDE(), 2));
            }
        }
        $builder = $this->createFormBuilder($teamgame)
            ->add('startingtime', TimeType::class, array('label' => ' Tee Time', 'widget' => 'single_text', 'required' => true, 'attr' => array('class' => 'form-control', 'style' => 'height: 45px;')))
            ->add('teamone', TextType::class, array('label' => 'Team One Name', 'required' => true, 'attr' => array('class' => 'form-control', 'style' => 'height: 45px;')))
            ->add('teamOnePlayerIdsCollection', CollectionType::class, ['entry_type' => ChoiceType::class, 'entry_options' => [ 'choices' => $playerChoices, 'attr' => ['style' => 'height: 50px;', 'class' => 'form-control'],],]);
            
            if ($event->getTeamsorplayerspergame() > 1) {
            $builder
                ->add('teamtwo', TextType::class, array('label' => 'Team Two Name', 'required' => false, 'attr' => array('class' => 'form-control', 'style' => 'height: 45px;')))
                ->add('teamTwoPlayerIdsCollection', CollectionType::class, ['entry_type' => ChoiceType::class, 'entry_options' => [ 'choices' => $playerChoices, 'attr' => ['style' => 'height: 50px;', 'class' => 'form-control'],],]);
        } 
        $builder
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')));

        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $form = $builder->getForm();
        return $form;
    }

    /**
     * @param Request $request
     * @param int $event_id
     * @param int $teamgame_id
     *
     * @return RedirectResponse
     * @throws Exception
     */
    #[Route('/teamgame/delete/{event_id}/{teamgame_id}', name: 'teamgame_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, int $event_id, int $teamgame_id): RedirectResponse {
        $teamgameRepository = new TeamgameRepository($this->em, $this->logger);
        $teamgame = $teamgameRepository->find($teamgame_id);
        $teamgameRepository->removeTeamgame($teamgame);

        $parameters = array('id' => $event_id);
        return $this->redirectToRoute('event_edit', $parameters);
    }

    /**
     * @param Request $request
     * @param int $event_id
     * @param int $teamgame_id
     * @param int $gamenumber
     *
     * @return RedirectResponse|Response
     */
    #[Route('/teamgame/edit/{event_id}/{teamgame_id}/{gamenumber}', name: 'teamgame_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, int $event_id, int $teamgame_id, int $gamenumber): RedirectResponse|Response {
        $user = $this->getUser();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $user->getLeague();
        
        $teamgameRepository = new TeamgameRepository($this->em, $this->logger);
        $teamGame = $teamgameRepository->find($teamgame_id);

        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($event_id);

        $form = $this->buildTeamgameForm($teamGame, $league, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $teamGame = $form->getData();
                $this->updatePlayerCollection($teamGame, $teamGame->getTeamOnePlayerIds(), 1);
                
                if ($event->getPlayersperteam() < 4) {
                    $this->updatePlayerCollection($teamGame, $teamGame->getTeamTwoPlayerIds(), 2);
                }
                $teamgameRepository->saveTeamgame($teamGame);
                
                $parameters = array('id' => $event->getId());
                return $this->redirectToRoute('event_edit', $parameters);
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected game: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        return $this->render('teamgame/edit.html.twig',
            array(
                'title' => "Edit Team Game",
                'event' => $event,
                'gamenumber' => $gamenumber,
                'teamgame' => $teamGame,
                'form' => $form->createView()));
    }

    /**
     * @param Form $form
     * @param GameScoresFormBean $formbean
     *
     * @return boolean valid form
     * @noinspection PhpUnusedPrivateMethodInspection*/
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
     * @param Request $request
     * @param int $event_id event id
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/teamgame/new/{event_id}', name: 'teamgame_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, int $event_id): Response {
        $user = $this->getUser();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $user->getLeague();
        
        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($event_id);
        
        $teamGame = new TeamgameDE($event);
        $form = $this-> buildTeamgameForm($teamGame, $league, $event);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $teamGame = $form->getData();
            $eventRepository = new EventRepository($this->em, $this->logger);
            $event = $eventRepository->find($event_id);
            $teamGame->setEvent($event);
            
            $this->updatePlayerCollection($teamGame, $teamGame->getTeamOnePlayerIds(), 1);
            
            if ($event->getPlayersperteam() < 4) {
                $this->updatePlayerCollection($teamGame, $teamGame->getTeamTwoPlayerIds(), 2);
            }
            $event->getTeamgames()->add($teamGame);
            $eventRepository->saveEvent($event);
            
            $session = $event->getSession();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $season = $session->getSeason();
            
            $parameters = array('id' => $event_id);
            return $this->redirectToRoute('event_edit', $parameters);
        }
        return $this->render('teamgame/new.html.twig',
            [
                'title' => "Add Team Game",
                'event' => $event,
                'form' => $form->createView()
            ]
        );
    }

    /**
     * @param ScoreBean $scoreBean
     * @param DateTime $startingdateandtime
     *
     * @return ScoreDE|null
     * @throws Exception
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function playerScore(ScoreBean $scoreBean, DateTime $startingdateandtime): ?ScoreDE {
        $playerScore = $scoreBean->getScore();
        
        if (empty($playerScore)) {
            $playerScore = new ScoreDE();
        }
        
        $playerScore->setPlayer($scoreBean->getPlayer());
        $playerScore->setTee($scoreBean->getTee());
        $playerScore->setStrokes(ScoreDE::packIntArray($scoreBean->getStrokes()));
        $playerScore->setTimestamp(clone $startingdateandtime);
        
        if (!$playerScore->getDuplicatescore()) {
            $playerScore->setDuplicatescore($scoreBean->getDuplicate());
        }
        $playerScore->setPartialscore($scoreBean->getPartial());
        
        $scoreRepository = new ScoreRepository($this->em, $this->logger);
        $scores = $scoreRepository->findPlayerScores($scoreBean->getPlayer(), $startingdateandtime);
        
        if (sizeof($scores) > 20) {
            $scores = array_slice($scores, 0, 20);
        }
        $scoresRecorded = sizeof($scores);
        $playerHandicapCalculationResult = $scoreRepository->calculatePlayerHandicapIndex($scoreBean->getPlayer(), $startingdateandtime, $scores);
        
        $playerScore->setCurrenthandicapindex($playerHandicapCalculationResult['currentHandicapIndex']);
        $playerScore->setHandicapdifferential($playerScore->calculateHandicapDifferential($scoresRecorded));
        
        return $playerScore;
    }

    /**
     * @param Request $request
     * @param int $gamenumber
     * @param int $event_id
     * @param int $teamgame_id
     *
     * @return RedirectResponse|Response
     */
    #[Route('/teamgame/post/scores/{event_id}/{teamgame_id}/{gamenumber}', name: 'teamgame_post_scores', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function postScores(Request $request, int $gamenumber, int $event_id, int $teamgame_id): RedirectResponse|Response {
        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($event_id);
        
        $teamgameRepository = new TeamGameRepository($this->em, $this->logger);
        $teamgame = $teamgameRepository->find($teamgame_id);

        $form = $this->buildTeamGameScoresForm($event, $teamgame);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $teamgame = $form->getData();
            
            try {
                $teamgame->setRecorded(true);
                $teamgameRepository->saveTeamgame($teamgame);
                
                $season = $event->getSession()->getSeason();
                $parameters = array('id' => $season->getId());
                return $this->redirectToRoute('event_list', $parameters);
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected game: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        if ($event->isScramble($event->getFormat())) {
            $template = 'teamgame/post.scramblescores.html.twig';
        } else {
            $template = 'teamgame/post.scores.html.twig';
        }
        return $this->render($template,
            array(
                'title' => "Post Team Game Scores",
                'event' => $event,
                'teamgame' => $teamgame,
                'gamenumber' => $gamenumber,
                'form' => $form->createView()));
    }

    /**
     * @param GameScoresFormBean $formbean
     * @param EventDE $event
     * @param GameDE $game
     *
     * @noinspection PhpUnusedPrivateMethodInspection*/
    private function saveGameScores(GameScoresFormBean $formbean, EventDE $event, GameDE &$game): void {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $tee = $event->getTee();
        /** @noinspection PhpUnusedLocalVariableInspection */
        $startingdateandtime = $event->getStartdateandtime();
    }

    /**
     * @param int $event_id
     * @param int $teamgame_id
     * @param int $gamenumber
     *
     * @return Response
     */
    #[Route('/teamgame/view/{event_id}/{teamgame_id}/{gamenumber}', name: 'teamgame_view', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function view(int $event_id, int $teamgame_id, int $gamenumber): Response {
        $eventRepository = new EventRepository($this->em, $this->logger);
        $event = $eventRepository->find($event_id);

        $teamgameRepository = new TeamgameRepository($this->em, $this->logger);
        $teamgame = $teamgameRepository->find($teamgame_id);

        if ($teamgame == null) {
            return $this->render('error/error.html.twig',
                ['title' => 'Error', 'e' => 'There are no teamgames that match the criteria specified.'] );
        } else {
            $season = $event->getSession()->getSeason();

            return $this->render( 'teamgame/view.html.twig',
                [
                    'title'    => 'Team Game',
                    'teamgame' => $teamgame,
                    'event'    => $event,
                    'season'   => $season
                ]
            );
        }
    }
    
    /**
     * Update player collection as appropriate
     * 
     * @param TeamgameDE $teamgame
     * @param int[] $playerIds
     * @param int $teamNumber
     */
    private function updatePlayerCollection(TeamgameDE $teamgame, ArrayCollection $playerIdCollection, int $teamNumber): void {
        $playerIds = $playerIdCollection->toArray();

        foreach($teamgame->getPlayers() as $teamplayer) {
            $removeTeamPlayer = true;
            
            if ($teamplayer->getTeamnumber() == $teamNumber) {
                foreach($playerIds as $playerId) {
                    if ($teamplayer->getPlayer()->getId() == $playerId) {
                        $removeTeamPlayer = false;

                        $key = array_search($playerId, $playerIds);
                        if ($key !== false) {
                            unset($playerIds[$key]);
                        }
                        break;
                    }
                }
            } else {
                $removeTeamPlayer = false;
            }
            if ($removeTeamPlayer) {
                $teamgame->getPlayers()->removeElement($teamplayer);
            }
        }
        $playerRepository = new PlayerRepository($this->em, $this->logger);

        foreach($playerIds as $playerId) {
            $player = $playerRepository->find($playerId);
            $teamgame->getPlayers()->add(new TeamgameplayerDE($player, $teamNumber));
        }
    }
}