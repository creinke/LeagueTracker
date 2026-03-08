<?php
namespace App\Controller;

use App\Entity\UserDE;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Exception;
use App\Repository\LeagueRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController {
	private EntityManagerInterface $em;
	private LoggerInterface $logger;
	private UserPasswordHasherInterface $passwordHasher;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger, UserPasswordHasherInterface $passwordHasher) {
		$this->em = $em;
		$this->logger = $logger;
		$this->passwordHasher = $passwordHasher;
	}

	private function buildForm(UserDE &$user) : FormInterface {
        if ($user->getLeague()) {
            $user->setLeagueName($user->getLeague()->getName());
        }
        $roleList = "";

        if ($user->getRoles()) {
            $roles = $user->getRoles();
            $roleCount = sizeof($roles);

            for($i = 0; $i < $roleCount; $i++) {
                $role = $roles[$i];

                $roleList .= $role;

                if ($i + 1 < $roleCount) {
                    $roleList .= ", ";
                }
            }
            $user->setRoleList($roleList);
        }
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        $form = $this->createFormBuilder($user)
            ->add('username', TextType::class, array('label' => 'User Name', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('leagueName', TextType::class, array('label' => 'League Name', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('plainPassword', PasswordType::class, array('label' => 'Password', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('roleList', TextType::class, array('label' => 'Roles', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')))
            ->getForm();

        return $form;
    }

	#[Route('/user/delete/{id}', name: 'user_delete', methods: ['DELETE'])]
	#[IsGranted('ROLE_SUPER')]
    public function delete(Request $request, int $id): RedirectResponse {
        $userRepository = new UserRepository($this->em, $this->logger, $this->passwordHasher);
        $user = $userRepository->find($id);

        try {
            $userRepository->removeUser($user);
        } catch (Exception $e) {
	        $this->addFlash('error', 'Trouble deleting selected user: ' . $e->getMessage() . ' Please retry.');
        }
        return $this->redirectToRoute('user_list');
    }

	#[Route('/user/edit/{id}', name: 'user_edit', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_SUPER')]
	public function edit(Request $request, int $id, UserPasswordHasherInterface $passwordHasher): RedirectResponse|Response {
        $userRepository = new UserRepository($this->em, $this->logger, $passwordHasher);
        $user = $userRepository->find($id);

        $form = $this->buildForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $leagueRepository = new LeagueRepository($this->em, $this->logger);
                $league = $leagueRepository->findLeagueByName($user->getLeagueName());

                if ($league) {
                    $user->setLeague($league);

	                $password = $passwordHasher->hashPassword($user, $user->getPlainPassword());
					$user->setPassword($password);

                    $roles = array();
                    $s = explode(", ", $user->getRoleList());

                    foreach($s as $role) {
                        $roles[] = $role;
                    }
                    $user->setRoles($roles);

                    $userRepository->saveUser($user);
                    return $this->redirectToRoute('user_list');
                } else {
                    $form->addError(new FormError('Invalid League Name: ' . $user->getLeagueName() . ' Please fix and retry.'));
                }
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected user: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        return $this->render('user/edit.html.twig',
            array(
                'title' => "Edit User",
                'form' => $form->createView()));
    }

    /**
     * @return Response
     */
	#[Route('/user/list', name: 'user_list', methods: ['GET'])]
	#[IsGranted('ROLE_SUPER')]
    public function list(): Response {
        $userRepository = new UserRepository($this->em, $this->logger, $this->passwordHasher);
        $users = $userRepository->findAll();

        return $this->render('user/list.html.twig',
            array(
                'title' => 'Users',
                'users' => $users)
            );
    }

	/**
	 * @param Request $request
	 * @param UserPasswordHasherInterface $passwordEncoder
	 *
	 * @return Response
	 * @throws Exception
	 */
	#[Route('/user/new', name: 'user_new', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_SUPER')]
	public function new(Request $request, UserPasswordHasherInterface $passwordEncoder): Response {
		$currentUser = $this->getUser();  // Get the current logged-in UserDE
		$user = new UserDE($this->em);

		// Prefill the new user's league from the current user's league
		if ($currentUser instanceof UserDE && $currentUser->getLeague()) {
			$user->setLeague($currentUser->getLeague());
		}

		$form = $this-> buildForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $userRepository = new UserRepository($this->em, $this->logger, $passwordEncoder);

            $leagueRepository = new LeagueRepository($this->em, $this->logger);
            $league = $leagueRepository->findLeagueByName($user->getLeagueName());

            if ($league) {
                $user->setLeague($league);

	            $password = $passwordEncoder->hashPassword($user, $user->getPlainPassword());
	            $user->setPassword($password);

                $roles = array();
                $s = explode(", ", $user->getRoleList());

                foreach($s as $role) {
                    $roles[] = $role;
                }
                $user->setRoles($roles);

                $userRepository->saveUser($user);
                return $this->redirectToRoute('user_list');
            } else {
                $form->addError(new FormError('Invalid League Name: ' . $user->getLeagueName() . ' Please fix and retry.'));
            }
        }
        return $this->render('user/new.html.twig',
            array(
                'title' => "Add User",
                'form' => $form->createView()));
    }

	#[Route('/user/view/{id}', name: 'user_view', methods: ['GET'])]
	#[IsGranted('ROLE_SUPER')]
    public function view(int $id): Response {
        $userRepository = new UserRepository($this->em, $this->logger, $this->passwordHasher);
        $user = $userRepository->find($id);

		if ($user == null) {
			return $this->render('error/error.html.twig',
				['title' => 'Error', 'e' => 'There are no users that match the criteria specified.'] );
		} else {
			$user->setLeagueName( $user->getLeague()->getName() );

			$roleList  = "";
			$roles     = $user->getRoles();
			$roleCount = sizeof( $roles );

			for ( $i = 0; $i < $roleCount; $i ++ ) {
				$role = $roles[ $i ];

				$roleList .= $role;

				if ( $i + 1 < $roleCount ) {
					$roleList .= ", ";
				}
			}
			$user->setRoleList( $roleList );

			return $this->render( 'user/view.html.twig',
				array(
					'title' => 'User',
					'user'  => $user
				)
			);
		}
    }
}
