<?php

namespace App\Controller;

use App\Entity\PaymentDE;
use App\Entity\PlayerDE;
use App\Entity\FullnameDE;
use App\Form\PlayersFormBean;
use App\Form\Type\PlayerType;
use App\Model\DoctrineTrait;
use App\Repository\PaymentRepository;
use App\Repository\PlayerRepository;
use App\Repository\PlayermatchRepository;
use App\Repository\ScoreRepository;
use App\View\PlayerHandicapViewBean;
use Doctrine\ORM\EntityManager;
use \DateTime;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\LeagueRepository;

class PaymentController extends AbstractController {
    use DoctrineTrait;

    /**
     * @var EntityManager
     */
    private $em;

    private function buildForm(PaymentDE $payment, $editForm = false) : Form {
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        $builder = $this->createFormBuilder($payment)
            ->add('carryoveramount', NumberType::class, array('scale' => 2, 'label' => 'Carry Over', 'required' => false, 'attr' => array('class' => 'form-control')))
            ->add('paymentamount', NumberType::class, array('scale' => 2, 'label' => 'Payment', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')));

        if ($editForm) {
            $builder->add('defunct', CheckboxType::class, array('label' => 'Defuncted', 'required' => false));

        }
        $form = $builder->getForm();
        return $form;
    }

    private function buildPlayersForm(PlayersFormBean $formbean) : Form {
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        $form = $this->createFormBuilder($formbean)
            ->add('players', CollectionType::class, ['entry_type' => PlayerType::class])
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3')))
            ->getForm()
        ;

        return $form;
    }

    /**
     * @Route("/payment/delete/{id}", name="payment_delete")
     * @IsGranted("ROLE_ADMIN")
     * @Method("DELETE")
     */
    public function delete(Request $request, $id) {
        $this->em = $this->getEntityManager();

        $paymentRepository = new PaymentRepository($this->em);
        $payment = $paymentRepository->find($id);

        try {
            $paymentRepository->removePayment($payment);
        } catch (Exception $e) {
            $form->addError(new FormError('Trouble updating/deleting selected payment: ' . $e->getMessage() . ' Please retry.'));
        }

        return $this->redirectToRoute('payment_list');
    }

    /**
     * @Route("/payment/edit/{id}", name="payment_edit")
     * @IsGranted("ROLE_USER")
     * @Method({"GET", "POST"})
     */
    public function edit(Request $request, $id) {
        $this->em = $this->getEntityManager();

        $paymentRepository = new PaymentRepository($this->em);
        $payment = $paymentRepository->find($id);

        $form = $this->buildForm($payment, true);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $paymentRepository->savePayment($payment);
                return $this->redirectToRoute('payment_list');
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected payment: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        return $this->render('payment/edit.html.twig',
            array(
                'title' => "Edit Payment",
                'form' => $form->createView()));
    }

    /**
     * @Route("/payment/list", name="payment_list")
     * @IsGranted("ROLE_USER")
     * @Method("GET")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function list() {
        $this->em = $this->getEntityManager();

        $league = $this->getUser()->getLeague();
        $leagueId = $league->getId();
        $leagueName = $league->getName();

        $paymentRepository = new PaymentRepository($this->em);
        $payments = $paymentRepository->findAllPayments($leagueId);

        return $this->render('payment/list.html.twig',
            array(
                'title' => 'Payments',
                'league' => $leagueName,
                '$payments' => $payments)
            );
    }

    /**
     * @Route("/payment/new", name="payment_new")
     * @IsGranted("ROLE_USER")
     * @Method({"GET", "POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function new(Request $request) {
        $this->em = $this->getEntityManager();

        $payment = new  PaymentDE($this->em);
        $player->setName(new FullnameDE());

        $form = $this-> buildForm($player, false);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em = $this->getEntityManager();

            $leagueRepository = new LeagueRepository($this->em);
            $league = $leagueRepository->findById($this->getUser()->getLeague()->getId());

            $payment = $form->getData();
            $playerRepository = new PlayerRepository($this->em);
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
     * @Route("/player/newlist", name="player_newlist")
     * @IsGranted("ROLE_ADMIN")
     * @Method({"GET", "POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newlist(Request $request) {
        $this->em = $this->getEntityManager();

        $user = $this->getUser();
        $league = $user->getLeague();
        $_SESSION['league'] = $league;

        $playersFormBean = new PlayersFormBean();
        $playersFormBean->populate($this->em, $league);
        
        $form = $this-> buildPlayersForm($playersFormBean);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $players = $form->getData()->getPlayers();
            $playerRepository = new PlayerRepository($this->em);

            $leagueRepository = new LeagueRepository($this->em);
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
     * @param PlayerDE $player
     */
    private function sanitizePlayerName(PlayerDE $player) {
        $name = $player->getName();
        if (empty($name->getMiddlenameorinitial())) {
            $name->setMiddlenameorinitial('');
        }
        if (empty($name->getGeneration())) {
            $name->setGeneration('');
        }
        $nameData = array('firstName' => $name->getFirstname(), 'lastName' => $name->getLastname(), 'middleNameOrInitial' => $name->getMiddlenameorinitial(), 'generation' => $name->getGeneration());
        return $nameData;
    }

    /**
     * @Route("/player/undefunct/{id}", name="player_undefunct")
     * @IsGranted("ROLE_ADMIN")
     * @Method("POST")
     */
    public function undefunct(Request $request, $id) {
        $this->em = $this->getEntityManager();

        $user = $this->getUser();
        $league = $user->getLeague();

        $playerRepository = new PlayerRepository($this->em);
        $player = $playerRepository->find($id);
        $player->setDefunct(false);

        try {
            $playerRepository->savePlayer($player);
        } catch (Exception $e) {
            $form->addError(new FormError('Trouble updating selected player: ' . $e->getMessage() . ' Please retry.'));
        }
        return $this->redirectToRoute('player_list');
    }

    /**
     * @Route("/player/view/{id}", name="player_view")
     * @IsGranted("ROLE_USER")
     * @Method("GET")
     */
    public function view($id) {
        $this->em = $this->getEntityManager();

        $playerRepository = new PlayerRepository($this->em);
        $player = $playerRepository->find($id);

        return $this->render('player/view.html.twig',
            array(
                'title' => 'Player',
                'player' => $player)
            );
    }
}