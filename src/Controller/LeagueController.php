<?php
namespace App\Controller;

use App\Entity\LeagueDE;
use App\Model\DoctrineTrait;
use App\Repository\LeagueRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse ;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use App\Repository\CourseRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LeagueController extends AbstractController {
	private EntityManagerInterface $em;
	private LoggerInterface $logger;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		$this->em = $em;
		$this->logger = $logger;
	}

	/**
	 * @param LeagueDE $league
	 *
	 * @return FormInterface
	 */
	private function buildForm(LeagueDE $league) : FormInterface {
        $disableSaveButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

        $form = $this->createFormBuilder($league)
            ->add('name', TextType::class, array('label' => 'League Name', 'required' => true, 'attr' => array('class' => 'form-control')))
            ->add('save', SubmitType::class, array('label' => 'Save', 'disabled' => $disableSaveButton, 'attr' => array('class' => 'btn btn-primary mt-3', 'style' => 'margin-top: 2em;')))
            ->getForm();

        return $form;
    }

	/**
	 * @param Request $request
	 *
	 * @return Response
	 */
	#[Route('/league/courses', name: 'league_courses', methods: ['GET'])]
	#[IsGranted('ROLE_ADMIN')]
	public function courses(Request $request): Response {
        $league = $this->getUser()->getLeague();

        $courseRepository = new CourseRepository($this->em, $this->logger);;
        $elementName = $_GET["name"];
        preg_match("/([0-9]+)/", $elementName, $matches);
        $eventNumber = $matches[1];

        $courseName = $_GET["val"];
        $course = $courseRepository->findCourseByName($courseName);

        $selected = false;
        $html = '<html lang=""><body><nines>';
        $html .= '<select id="season_events_' . $eventNumber . '_nineName" name="season[events][' . $eventNumber . '][nineName]" class="form-control" style="height: 2.1em;">';

        foreach($course->getNines() as $nine) {
            $html .= '<option value="' . $nine->getName() . '"';

            if (!$selected) {
                $selected = true;
                $html .= ' selected="selected"';
            }
            $html .= '>' . $nine->getName() . '</option>';
        }
        $html .= '</select></nines>';

        $selected = false;
        $nine = $course->getNines()[0];
        $html .= '<tees><select id="season_events_' . $eventNumber . '_teeName" name="season[events][' . $eventNumber . '][teeName]" class="form-control" style="height: 2.1em;">';

        foreach($nine->getTees() as $tee) {
            $html .= '<option value="' . $tee->getName() . '"';

            if (!$selected) {
                $selected = true;
                $html .= ' selected="selected"';
            }
            $html .= '>' . $tee->getName() . '</option>';
        }
        $html .= '</select></tees></body></html>';

        return new Response($html);
    }

	/**
	 * @param Request $request
	 * @param $id
	 *
	 * @return RedirectResponse
	 */
	#[Route('/league/delete/{id}', name: 'league_delete', methods: ['DELETE'])]
	#[IsGranted('ROLE_SUPER')]
    public function delete(Request $request, $id): RedirectResponse {
        $leagueRepository = new LeagueRepository($this->em, $this->logger);
        $league = $leagueRepository->find($id);

        try {
            $leagueRepository->removeLeague($league);
        } catch (Exception $e) {
	        $this->addFlash('error', 'Trouble deleting selected league: ' . $e->getMessage() . ' Please retry.');
        }
        return $this->redirectToRoute('league_list');
    }

	/**
	 * @param Request $request
	 * @param $id
	 *
	 * @return RedirectResponse|Response
	 */
	#[Route('/league/edit/{id}', name: 'league_edit', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_SUPER')]
    public function edit(Request $request, $id): RedirectResponse|Response {
        $leagueRepository = new LeagueRepository($this->em, $this->logger);
        $league = $leagueRepository->find($id);

        $form = $this->buildForm($league);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $leagueRepository->saveLeague($league);
                return $this->redirectToRoute('league_list');
            } catch (Exception $e) {
                $form->addError(new FormError('Trouble updating selected league: ' . $e->getMessage() . ' Please retry.'));
            }
        }
        return $this->render('league/edit.html.twig',
            array(
                'title' => "Edit League",
                'form' => $form->createView()));
    }

    /**
     * @return Response
     */
	#[Route('/league/list', name: 'league_list', methods: ['GET'])]
	#[IsGranted('ROLE_ADMIN')]
	public function list(): Response {
        $leagueRepository = new LeagueRepository($this->em, $this->logger);
        $leagues = $leagueRepository->findAll();

        return $this->render('league/list.html.twig',
            array(
                'title' => 'Leagues',
                'leagues' => $leagues)
            );
    }

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @throws Exception
	 */
	#[Route('/league/new', name: 'league_new', methods: ['GET', 'POST'])]
	#[IsGranted('ROLE_ADMIN')]
	public function new(Request $request): Response {
        $league = new  LeagueDE($this->em);
        $form = $this-> buildForm($league);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $league = $form->getData();
            $leagueRepository = new LeagueRepository($this->em, $this->logger);

            $leagueRepository->saveLeague($league);
            return $this->redirectToRoute('league_list');
        }
        return $this->render('league/new.html.twig',
            array(
                'title' => "Add League",
                'form' => $form->createView()));
    }

	/**
	 * @param $id
	 *
	 * @return Response
	 */
	#[Route('/league/view/{id}', name: 'league_view', methods: ['GET'])]
	#[IsGranted('ROLE_ADMIN')]
	public function view($id): Response {
        $leagueRepository = new LeagueRepository($this->em, $this->logger);
        $league = $leagueRepository->find($id);

		if ($league == null) {
			return $this->render('error/error.html.twig',
				['title' => 'Error', 'e' => 'There are no leagues that match the criteria specified.'] );
		} else {
			return $this->render( 'league/view.html.twig',
				array(
					'title'  => 'League',
					'league' => $league
				)
			);
		}
    }
}
