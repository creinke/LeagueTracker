<?php

namespace App\Controller;

use App\Entity\PlayerDE;
use App\Entity\FullnameDE;
use App\Form\PlayersFormBean;
use App\Form\Type\PlayerType;
use App\Repository\PlayerRepository;
use App\Repository\PlayermatchRepository;
use App\Repository\ScoreRepository;
use App\View\PlayerHandicapViewBean;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\LeagueRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PlayerController extends AbstractController {
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param PlayerDE $player
     * @param bool $editForm
     *
     * @return FormInterface
     */
    private function buildForm(PlayerDE $player, bool $editForm = false) : FormInterface {
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        $builder = $this->createFormBuilder($player)
            ->add('firstname', TextType::class, array('label' => 'First Name', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('middlenameorinitial', TextType::class, array('label' => 'Middle Name or Initial', 'required' => false, 'attr' => array('class' => 'form-control')))
            ->add('lastname', TextType::class, array('label' => 'Last Name', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('generation', ChoiceType::class, array('label' => 'Generation', 'required' => false, 'attr' => array('class' => 'form-control', 'style' => 'height: 45px;'),
                  'choices'  => array('' => '', 'JR' => 'JR', 'SR' => 'SR', "III" => 'III')))
            ->add('seedhandicapindex', NumberType::class, array('scale' => 2, 'label' => 'Seed Handicap Index', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('type', ChoiceType::class, array('label' => 'Player Type', 'required' => true, 'attr' => array('class' => 'form-control', 'style' => 'height: 45px;'),
                  'choices'  => array('Regular' => 'REGULAR', 'Sub' => 'SUB')))
            ->add('workemailaddress', EmailType::class, array('label' => 'Work Email Address', 'required' => false, 'attr' => array('class' => 'form-control')))
            ->add('personalemailaddress', EmailType::class, array('label' => 'Personal Email Address', 'required' => false, 'attr' => array('class' => 'form-control')))
            ->add('otheremailaddress', EmailType::class, array('label' => 'Other Email Address', 'required' => false, 'attr' => array('class' => 'form-control')))
            ->add('workphonenumber', TextType::class, array('label' => 'Work Phone Number', 'required' => false, 'attr' => array('class' => 'form-control')))
            ->add('cellphonenumber', TextType::class, array('label' => 'Cell Phone Number', 'required' => false, 'attr' => array('class' => 'form-control')))
            ->add('homephonenumber', TextType::class, array('label' => 'Home Phone Number', 'required' => false, 'attr' => array('class' => 'form-control')))
            ->add('otherphonenumber', TextType::class, array('label' => 'Other Phone Number', 'required' => false, 'attr' => array('class' => 'form-control')))
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')));

        if ($editForm) {
            $builder->add('defunct', CheckboxType::class, array('label' => 'Defuncted', 'required' => false));

        }
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $form = $builder->getForm();
        return $form;
    }

    /**
     * @param PlayersFormBean $formbean
     *
     * @return FormInterface
     */
    private function buildPlayersForm(PlayersFormBean $formbean) : FormInterface {
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $form = $this->createFormBuilder($formbean)
            ->add('players', CollectionType::class, ['entry_type' => PlayerType::class])
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3')))
            ->getForm();

        return $form;
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return RedirectResponse
     */
    #[Route('/player/delete/{id}', name: 'player_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, $id): RedirectResponse {
        $playerRepository = new PlayerRepository($this->em, $this->logger);
        $player = $playerRepository->find($id);

        try {
            $playermatchRepository = new PlayermatchRepository($this->em, $this->logger);
            $playermatch = $playermatchRepository->findOneByPlayerId($player->getId());

            if (empty($playermatch)) {
                $scoreRepository = new ScoreRepository($this->em, $this->logger);
                $scores = $scoreRepository->findPlayerScores($player, new DateTime());

                foreach($scores as $score) {
                    $scoreRepository->removeScore($score);
                }
                $playerRepository->removePlayer($player);
            } else {
                $player->setDefunct(true);
                $playerRepository->savePlayer($player);
            }
        } catch (Exception $e) {
            $form = $this->buildForm($player);
            $form->addError(new FormError('Trouble updating/deleting selected player: ' . $e->getMessage() . ' Please retry.'));
        }

        return $this->redirectToRoute('player_list');
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return RedirectResponse|Response
     */
    #[Route('/player/edit/{id}', name: 'player_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, $id): RedirectResponse|Response {
        $playerRepository = new PlayerRepository($this->em, $this->logger);
        $player = $playerRepository->find($id);

        $form = $this->buildForm($player, true);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->sanitizePlayerName($player);

            try {
                $scoreRepository = new ScoreRepository($this->em, $this->logger);
                $scores = $scoreRepository->findPlayerScores($player, new DateTime());
                $scoresRecorded = $scores == NULL ? 0 : min(5, sizeOf($scores));
                $seedHandicapIndex = $player->getSeedhandicapindex();

                for ($x = 0; $x < $scoresRecorded; $x++) {
                    $score = $scores[$x];
                    if ($score->getCurrenthandicapindex() != $seedHandicapIndex) {
                        $score->setCurrenthandicapindex($seedHandicapIndex);
                        $score->setAdjustedStrokes($score->adjustStrokes($score->getHandicap(), $scoresRecorded));
                        $scoreRepository->saveScore($score);
                    }
                }
                $playerRepository->savePlayer($player);
                return $this->redirectToRoute('player_list');
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected player: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        return $this->render('player/edit.html.twig',
            array(
                'title' => "Edit Player",
                'form' => $form->createView()));
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/player/handicap/{id}', name: 'player_handicap', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function handicap(Request $request, $id): Response {
        $playerRepository = new PlayerRepository($this->em, $this->logger);
        $scoreRepository = new ScoreRepository($this->em, $this->logger);
        $player = $playerRepository->find($id);
        $result = $scoreRepository->calculatePlayerHandicapIndex($player, new DateTime());

        $scores = $result['scores'];
        $currentHandicapIndex = $result['currentHandicapIndex'];
        $handicapRelevantScores = $result['handicapRelevantScores'];

        $handicap = null;
        $slope = null;
        $par = null;
        $rating = null;

        $bag = $request->request;

        if ($bag->count() > 0) {
            $slope = intval($bag->get('slope'));
            $par = intval($bag->get('par'));
            $rating = floatval($bag->get('rating'));

            if ($slope > 0 && $par > 0 && $rating > 0) {
                $handicap = round((($currentHandicapIndex * $slope) / 113) + ($rating - $par));
            }
        }
        $playerHandicapViewBean = new PlayerHandicapViewBean($player, $currentHandicapIndex, $handicap, $scores, $handicapRelevantScores);

        return $this->render('player/handicap.html.twig',
            array(
                'id' => $player->getId(),
                'title' => 'Player',
                'player' => $player,
                'slope' => $slope,
                'par' => $par,
                'rating' => $rating,
                'view' => $playerHandicapViewBean)
            );
    }

    /**
     * @return Response
     * @throws Exception
     */
    #[Route('/player/list', name: 'player_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): Response {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $this->getUser()->getLeague();
        $leagueId = $league->getId();
        $leagueName = $league->getName();

        $playerRepository = new PlayerRepository($this->em, $this->logger);
        $scoreRepository = new ScoreRepository($this->em, $this->logger);

        $playerResult = $playerRepository->findAllPlayers($leagueId);
        $playersData = array();

        foreach($playerResult as $o) {
            if ($o instanceof PlayerDE) {
                $playerData = [];
                $playerData['player'] = $o;

                $playerInfo = $scoreRepository->calculatePlayerHandicapIndex($o, new DateTime());
                $playerData['info'] = $playerInfo;

                $playersData[] = $playerData;
            }
        }
        return $this->render('player/list.html.twig',
            array(
                'title' => 'Players',
                'league' => $leagueName,
                'playersData' => $playersData)
            );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/player/new', name: 'player_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response {
        $player = new  PlayerDE();
        $player->setName(new FullnameDE());

        $form = $this->buildForm($player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $leagueRepository = new LeagueRepository($this->em, $this->logger);
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $league = $leagueRepository->findById($this->getUser()->getLeague()->getId());

            $player = $form->getData();
            $nameData = $this->sanitizePlayerName($player);

            $playerRepository = new PlayerRepository($this->em, $this->logger);
            $queryResult = $playerRepository->findPlayerByName($league->getId(), $nameData);

            if (empty($queryResult)) {
                $player->setLeague($league);
                $playerRepository->savePlayer($player);

                return $this->redirectToRoute('player_list');
            } else {
                $form->addError(new FormError('Cannot add a player with the same name to this league'));
            }
        }
        return $this->render('player/new.html.twig',
            array(
                'title' => "Add Player",
                'form' => $form->createView()));
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/player/newlist', name: 'player_newlist', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function newlist(Request $request): Response {
        $user = $this->getUser();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $user->getLeague();
        $_SESSION['league'] = $league;

        $playersFormBean = new PlayersFormBean();
        $playersFormBean->populate($league);

        $form = $this-> buildPlayersForm($playersFormBean);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $players = $form->getData()->getPlayers();
            $playerRepository = new PlayerRepository($this->em, $this->logger);

            $leagueRepository = new LeagueRepository($this->em, $this->logger);
            $league = $leagueRepository->findById($league->getId());

            $errorsDetected = false;

            foreach($players as $player) {
                if (!empty($player->getFirstname() && !empty($player->getLastname()))) {
                    $nameData = $this->sanitizePlayerName($player);
                    $queryResult = $playerRepository->findPlayerByName($league->getId(), $nameData);

                    if (empty($queryResult)) {
                        $player->setLeague($league);
                        $league->getPlayers()->add($player);
                    } else {
                        $errorsDetected = true;
                        $form->addError(new FormError('Cannot add a player with the same name to this league: ' . $player->getName()->getFullname()));
                    }
                }
            }
            if (!$errorsDetected) {
                $leagueRepository->saveLeague($league);
                return $this->redirectToRoute('player_list');
            }
        }
        return $this->render('player/newlist.html.twig',
            array(
                'title' => "Add Players",
                'form' => $form->createView()));
    }

    /**
     * Check the player name and set unspecified fields to ""
     *
     * @param PlayerDE $player
     *
     * @return array
     */
    private function sanitizePlayerName(PlayerDE $player): array {
        $name = $player->getName();

        if (empty($name->getMiddlenameorinitial())) {
            $name->setMiddlenameorinitial('');
        }
        if (empty($name->getGeneration())) {
            $name->setGeneration('');
        }
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $nameData = array('firstName' => $name->getFirstname(), 'lastName' => $name->getLastname(), 'middleNameOrInitial' => $name->getMiddlenameorinitial(), 'generation' => $name->getGeneration());
        return $nameData;
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return RedirectResponse
     */
    #[Route('/player/undefunct/{id}', name: 'player_undefunct', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function undefunct(Request $request, $id): RedirectResponse {
        $playerRepository = new PlayerRepository($this->em, $this->logger);
        $player = $playerRepository->find($id);
        $player->setDefunct(false);

        try {
            $playerRepository->savePlayer($player);
        } catch (Exception $e) {
            $form = $this->buildForm($player);
            $form->addError(new FormError('Trouble updating selected player: ' . $e->getMessage() . ' Please retry.'));
        }
        return $this->redirectToRoute('player_list');
    }

    /**
     * @param $id
     *
     * @return Response
     */
    #[Route('/player/view/{id}', name: 'player_view', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function view($id): Response {
        $playerRepository = new PlayerRepository($this->em, $this->logger);
        $player = $playerRepository->find($id);

        if ($player == null) {
            return $this->render('error/error.html.twig',
                ['title' => 'Error', 'e' => 'There are no seasons that match the criteria specified.'] );
        } else {
            return $this->render( 'player/view.html.twig',
                array(
                    'title'  => 'Player',
                    'player' => $player
                )
            );
        }
    }
}