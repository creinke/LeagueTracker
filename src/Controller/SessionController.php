<?php

namespace App\Controller;

use App\Entity\SessionDE;
use App\Repository\SeasonRepository;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SessionController extends AbstractController {
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param SessionDE $session
     * @return FormInterface
     */
    private function buildSessionForm(SessionDE $session) : FormInterface {
        /** @noinspection DuplicatedCode */
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $form = $this->createFormBuilder($session)
            ->add('name', TextType::class, array('label' => 'Name', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('startdate', DateType::class, array('label' => 'Start Date', 'required' => true, 'widget' => 'single_text', 'attr' => array('class' => 'form-control')))
            ->add('enddate', DateType::class, array('label' => 'End Date', 'required' => true, 'widget' => 'single_text', 'attr' => array('class' => 'form-control')))
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')))
            ->getForm();
            
        return $form;
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return RedirectResponse
     * @throws Exception
     */
    #[Route('/session/delete/{id}', name: 'session_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, $id): RedirectResponse {
        $sessionRepository = new SessionRepository($this->em, $this->logger);
        $session = $sessionRepository->find($id);
        $season = $session->getSeason();
        $sessionRepository->removeSession($session);

        $parameters = ['id' => $season->getId()];
        $absoluteUrl = $this->generateUrl('session_list', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

        /** @noinspection PhpRedundantOptionalArgumentInspection */
        return $this->redirect($absoluteUrl, 302); // Explicitly use redirect() with status
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return RedirectResponse|Response
     */
    #[Route('/session/edit/{id}', name: 'session_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, $id): RedirectResponse|Response {
        $sessionRepository = new SessionRepository($this->em, $this->logger);
        $session = $sessionRepository->find($id);

        $form = $this->buildSessionForm($session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $session = $form->getData();
                $season = $session->getSeason();
                $sessionRepository->saveSession($session);

                $parameters = array('id' => $season->getId());
                return $this->redirectToRoute('session_list', $parameters);
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected session: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        return $this->render('session/edit.html.twig',
            array(
                'title' => "Edit Session",
                'session' => $session,
                'form' => $form->createView()
            )
        );
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return Response
     */
    #[Route('/session/list/{id}', name: 'session_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(Request $request, $id): Response {
        $seasonRepository = new SeasonRepository($this->em, $this->logger);
        $season = $seasonRepository->findById($id);
        $sessions = $season->getSessions();
        
        return $this->render('session/list.html.twig',
            array(
                'title' => 'Sessions',
                'season' => $season,
                'sessions' => $sessions
            )
        );
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/session/new/{id}', name: 'session_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, $id): Response {
        $session = new SessionDE();
        
        $form = $this-> buildSessionForm($session);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $session = $form->getData();
            
            $seasonRepository = new SeasonRepository($this->em, $this->logger);
            $season = $seasonRepository->find($id);
            $session->setSeason($season);
            
            $sessionRepository = new SessionRepository($this->em, $this->logger);
            $sessionRepository->saveSession($session);
            
            $parameters = array('id' => $season->getId());
            return $this->redirectToRoute('session_list', $parameters);
        }
        return $this->render('session/new.html.twig',
            array(
                'title' => "Add Session",
                'form' => $form->createView()
            )
        );
    }

    /**
     * @param $id
     *
     * @return Response
     */
    #[Route('/session/view/{id}', name: 'session_view', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function view($id): Response {
        $sessionRepository = new SessionRepository($this->em, $this->logger);
        $session = $sessionRepository->find($id);

        if ($session == null) {
            return $this->render('error/error.html.twig',
                ['title' => 'Error', 'e' => 'There are no seasons that match the criteria specified.'] );
        } else {
            $season = $session->getSeason();

            return $this->render( 'session/view.html.twig',
                array(
                    'title'   => 'Session',
                    'season'  => $season,
                    'session' => $session
                )
            );
        }
    }
}