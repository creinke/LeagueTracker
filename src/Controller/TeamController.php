<?php
namespace App\Controller;

use App\Entity\PlayerDE;
use App\Entity\TeamDE;
use App\Form\TeamsFormBean;
use App\Form\Type\TeamType;
use App\Repository\LeagueRepository;
use App\Repository\PlayerRepository;
use App\Repository\TeamRepository;
use App\Repository\TeammatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Exception;

class TeamController extends AbstractController {
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param TeamDE $team
     * @param bool $editForm
     *
     * @return FormInterface
     */
    private function buildForm(TeamDE $team, bool $editForm = false) : FormInterface {
        $playerChoices = array();
        $league = $team->getLeague();
        $playerChoices[' '] = NULL;
        
        foreach($league->getPlayers() as $player) {
            $playerChoices[$player->getName()->getFullname()] = $player;
        }
        
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        $builder = $this->createFormBuilder($team)
            ->add('name', TextType::class, array('label' => 'Team Name', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('teamnumber', NumberType::class, array('label' => 'Team Number', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('players', CollectionType::class, ['entry_type' => ChoiceType::class, 'entry_options' => [ 'choices' => $playerChoices, 'attr' => ['style' => 'height: 45px;', 'class' => 'form-control'],],])
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')));

        if ($editForm) {
            $builder->add('defunct', CheckboxType::class, array('label' => 'Defuncted', 'required' => false));

        }
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $form = $builder->getForm();
        return $form;
    }

    /**
     * @param TeamsFormBean $formbean
     *
     * @return FormInterface
     */
    private function buildTeamsForm(TeamsFormBean $formbean) : FormInterface {
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $form = $this->createFormBuilder($formbean)
            ->add('teams', CollectionType::class, ['entry_type' => TeamType::class])
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3')))
            ->getForm()
        ;

        return $form;
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return RedirectResponse
     */
    #[Route('/team/delete/{id}', name: 'team_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, $id): RedirectResponse {
        $teamRepository = new TeamRepository($this->em, $this->logger );
        $team = $teamRepository->find($id);

        try {
            $teammatchRepository = new TeammatchRepository($this->em, $this->logger);
            $teammatch = $teammatchRepository->findOneByTeamId($team->getId());

            if (empty($teammatch)) {
                $teamRepository->removeTeam($team);
            } else {
                $team->setDefunct(true);
                $teamRepository->saveTeam($team);
            }
        } catch (Exception $e) {
            $this->addFlash('error', 'Trouble updating/deleting selected team: ' . $e->getMessage() . ' Please retry.');
        }
        return $this->redirectToRoute('team_list');
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return RedirectResponse|Response
     */
    #[Route('/team/edit/{id}', name: 'team_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, $id): RedirectResponse|Response {
        $user = $this->getUser();
        /** @noinspection PhpUnusedLocalVariableInspection */
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $user->getLeague();

        $teamRepository = new TeamRepository($this->em, $this->logger);
        $team = $teamRepository->find($id);
        
        while ($team->getPlayers()->count() < 4) {
            $team->getPlayers()->add(new PlayerDE());
        }

        $form = $this->buildForm($team, true);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach($team->getPlayers() as $player) {
                if (empty($player)) {
                    $team->getPlayers()->removeElement($player);
                }
            }
            try {
                $teamRepository->saveTeam($team);
                return $this->redirectToRoute('team_list');
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected team: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        return $this->render('team/edit.html.twig',
            array(
                'title' => "Edit Team",
                'form' => $form->createView()));
    }

    /**
     * @return Response
     * @throws Exception
     */
    #[Route('/team/list', name: 'team_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): Response {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $this->getUser()->getLeague();
        $leagueId = $league->getId();
        $leagueName = $league->getName();

        $teamRepository = new TeamRepository($this->em, $this->logger);
        $teams = $teamRepository->findAllTeams($leagueId);

        return $this->render('team/list.html.twig',
            array(
                'title' => 'Teams',
                'league' => $leagueName,
                'teams' => $teams)
            );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/team/new', name: 'team_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $this->getUser()->getLeague();
        $team = new TeamDE();
        $team->setLeague($league);
        $team->getPlayers()->add(new PlayerDE());
        $team->getPlayers()->add(new PlayerDE());
        $team->getPlayers()->add(new PlayerDE());
        $team->getPlayers()->add(new PlayerDE());
        
        $form = $this-> buildForm($team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team = $form->getData();
            
            foreach($team->getPlayers() as $player) {
                if (empty($player)) {
                    $team->getPlayers()->removeElement($player);
                }
            }
            $teamRepository = new TeamRepository($this->em, $this->logger);
            $queryResult = $teamRepository->findByName($league->getId(), $team->getName());

            $playerRepository = new PlayerRepository($this->em, $this->logger);
            $players = $team->getPlayers()->toArray();
            //$teamPlayers = $team->getPlayers();
            //$teamPlayers->clear();

            if (empty($queryResult)) {
                $leagueRepository = new LeagueRepository($this->em, $this->logger);
                $league = $leagueRepository->findById($league->getId());
                $team->setLeague($league);

                $playersCollection = new ArrayCollection();
                foreach ($players as $player) {
                    $playerToAdd = $playerRepository->find($player->getId());
                    if ($playerToAdd) {
                        $playersCollection->add($playerToAdd);
                    }
                }
                $team->setPlayers($playersCollection);
                $teamRepository->saveTeam($team);

                return $this->redirectToRoute('team_list');
            } else {
                $form->addError(new FormError('Cannot add a team with the same name to this league'));
            }
        }
        return $this->render('team/new.html.twig',
            array(
                'title' => "Add Team",
                'form' => $form->createView()));
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/team/newlist', name: 'team_newlist', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function newlist(Request $request): Response {
        $user = $this->getUser();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $user->getLeague();
        $_SESSION['league'] = $league;

        $teamsFormBean = new TeamsFormBean($league);
        $form = $this-> buildTeamsForm($teamsFormBean);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $teams = $form->getData()->getTeams();
            $teamRepository = new TeamRepository($this->em, $this->logger);

            $leagueRepository = new LeagueRepository($this->em, $this->logger);
            $league = $leagueRepository->findById($league->getId());

            $playerRepository = new PlayerRepository($this->em, $this->logger);

            $errorsDetected = false;

            foreach($teams as $team) {
                if (!empty($team->getName() && !empty($team->getTeamnumber()))) {
                    $queryResult = $teamRepository->findByName($league->getId(), $team->getName());

                    if (empty($queryResult)) {
                        $team->setLeague($league);

                        $players = $team->getPlayers()->toArray();
                        $team->getPlayers()->clear();

                        foreach($players as $player) {
                            if (!empty($player)) {
                                $team->getPlayers()->add($playerRepository->findById($player->getId()));
                            }
                        }
                        $league->getTeams()->add($team);
                    } else {
                        $errorsDetected = true;
                        $form->addError(new FormError('Cannot add a team with the same name to this league: ' . $team->getName()));
                    }
                }
            }
            if (!$errorsDetected) {
                $leagueRepository->saveLeague($league);
                return $this->redirectToRoute('team_list');
            }
        }
        return $this->render('team/newlist.html.twig',
            array(
                'title' => "Add Teams",
                'form' => $form->createView()));
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return RedirectResponse
     */
    #[Route('/team/undefunct/{id}', name: 'team_undefunct', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function undefunct(Request $request, $id): RedirectResponse {
        $user = $this->getUser();
        /** @noinspection PhpUnusedLocalVariableInspection */
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $league = $user->getLeague();

        $teamRepository = new TeamRepository($this->em, $this->logger);
        $team = $teamRepository->find($id);
        $team->setDefunct(false);

        try {
            $teamRepository->saveTeam($team);
        } catch (Exception $e) {
            $this->addFlash('error', 'Trouble updating selected team: ' . $e->getMessage() . ' Please retry.');
        }
        return $this->redirectToRoute('team_list');
    }

    /**
     * @param $id
     *
     * @return Response
     */
    #[Route('/team/view/{id}', name: 'team_view', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function view($id): Response {
        $teamRepository = new TeamRepository($this->em, $this->logger);
        $team = $teamRepository->find($id);

        if ($team == null) {
            return $this->render('error/error.html.twig',
                ['title' => 'Error', 'e' => 'There are no teams that match the criteria specified.'] );
        } else {
            return $this->render( 'team/view.html.twig',
                array(
                    'title' => 'Team',
                    'team'  => $team
                )
            );
        }
    }
}
