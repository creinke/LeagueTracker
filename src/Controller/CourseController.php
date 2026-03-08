<?php
namespace App\Controller;

use App\Entity\CourseDE;
use App\Entity\HoleDE;
use App\Entity\NineDE;
use App\Entity\TeeDE;
use App\Form\NewCourseFormBean;
use App\Form\Type\CourseType;
use App\Repository\CountryRepository;
use App\Repository\CourseRepository;
use App\Repository\RegionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller associated with the implementation of CRUD methods affecting Course Entities.
 */
#[IsGranted('ROLE_SUPER')]
class CourseController extends AbstractController {
	private EntityManagerInterface $em;
	private LoggerInterface $logger;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		$this->em = $em;
		$this->logger = $logger;
	}

	/**
	 * @param CourseDE $course
	 *
	 * @return FormInterface
	 */
	private function buildCourseForm(CourseDE $course): FormInterface {
		if (!empty($course->getAddress())) {
			$course->getAddress()->getRegion()->setCountryName($course->getAddress()->getRegion()->getCountry()->getName());
		}
		return $this->createForm(CourseType::class, $course);
	}

	/**
	 * @param NewCourseFormBean $formbean
	 *
	 * @return FormInterface
	 */
	private function buildNewCourseForm(NewCourseFormBean $formbean): FormInterface {
		$disableGenerateButton = in_array('ROLE_SAMPLE', $this->getUser()->getRoles());

		return $this->createFormBuilder($formbean)
            ->add('name', TextType::class, ['label' => 'Course Name', 'required' => true, 'attr' => ['class' => 'form-control']])
            ->add('numberOfNines', NumberType::class, ['label' => 'Number of Nines', 'required' => true, 'attr' => ['class' => 'form-control']])
            ->add('numberOfTees', NumberType::class, ['label' => 'Number of Tees', 'required' => true, 'attr' => ['class' => 'form-control']])
            ->add('generate', SubmitType::class, ['label' => 'Generate', 'disabled' => $disableGenerateButton, 'attr' => ['class' => 'btn btn-primary mt-3']])
            ->getForm();
	}

	/**
	 * @param Request $request
	 * @param int $id
	 *
	 * @return Response
	 * @throws Exception
	 */
	#[Route('/course/delete/{id}', name: 'course_delete', methods: ['DELETE'])]
	public function delete(Request $request, int $id): Response {
		$courseRepository = new CourseRepository($this->em, $this->logger);
		$course = $courseRepository->find($id);
		$courseRepository->removeCourse($course);

		return $this->redirectToRoute('course_list');
	}

	/**
	 * @param Request $request
	 * @param int $id
	 *
	 * @return Response
	 */
	#[Route('/course/edit/{id}', name: 'course_edit', methods: ['GET', 'POST'])]
	public function edit(Request $request, int $id): Response {
		$courseRepository = new CourseRepository($this->em, $this->logger);
		$course = $courseRepository->find($id);
		$form = $this->buildCourseForm($course);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$address = $course->getAddress();
			$regionRepository = new RegionRepository($this->em, $this->logger);
			$countryRepository = new CountryRepository($this->em, $this->logger);
			$country = $countryRepository->findCountryByName($address->getRegion()->getCountryName());
			$region = $regionRepository->findRegionByCode($address->getRegion()->getCode(), $country);
			$course->getAddress()->setRegion($region);

			// Tee & Hole propagation logic
			$nines = $course->getNines();
			if ($nines->count() > 0) {
				if ($nines->count() > 1) {
					$firstTees = $nines->get(0)->getTees();
					for ($n = 1; $n < $nines->count(); $n++) {
						$tees = $nines->get($n)->getTees();
						for ($t = 0; $t < $tees->count(); $t++) {
							$tee = $tees->get($t);
							if (empty($tee->getName())) {
								$tee->setName($firstTees->get($t)->getName());
							}
						}
					}
				}
				foreach ($nines as $nine) {
					$tees = $nine->getTees();
					if ($tees->count() > 1) {
						for ($t = 1; $t < $tees->count(); $t++) {
							$previousTee = $tees->get($t - 1);
							$tee = $tees->get($t);
							for ($h = 0; $h < 9; $h++) {
								$previousHole = $previousTee->getHoles()->get($h);
								$hole = $tee->getHoles()->get($h);
								if (empty($hole->getName()) && !empty($previousHole->getName())) $hole->setName($previousHole->getName());
								if (empty($hole->getHolenumber()) && !empty($previousHole->getHolenumber())) $hole->setHolenumber($previousHole->getHolenumber());
								if (empty($hole->getHandicap()) && !empty($previousHole->getHandicap())) $hole->setHandicap($previousHole->getHandicap());
								if (empty($hole->getPar()) && !empty($previousHole->getPar())) $hole->setPar($previousHole->getPar());
							}
						}
					}
				}
			}

			try {
				$courseRepository->saveCourse($course);
				return $this->redirectToRoute('course_list');
			} catch ( Exception $e) {
				$form->addError(new FormError('Trouble updating selected course: ' . $e->getMessage() . ' Please retry.'));
			}
		}

		return $this->render('course/edit.html.twig', [
			'title' => "Edit Course",
			'form' => $form->createView()
		]);
	}

	/**
	 * @return Response
	 */
	#[Route('/course/list', name: 'course_list', methods: ['GET'])]
	public function list(): Response {
		$courseRepository = new CourseRepository($this->em, $this->logger);
		$courses = $courseRepository->findAll();

		return $this->render('course/list.html.twig', [
			'title' => 'Courses',
			'courses' => $courses
		]);
	}

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @throws Exception
	 */
	#[Route('/course/new', name: 'course_new', methods: ['GET', 'POST'])]
	public function new(Request $request): Response {
		$formbean = new NewCourseFormBean();
		$form = $this->buildNewCourseForm($formbean);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$course = $form->getData();
			$courseRepository = new CourseRepository($this->em, $this->logger);
			$queryResult = $courseRepository->findCourseByName($formbean->getName());

			if (empty($queryResult)) {
				$course = new CourseDE($this->em);
				$course->setName($formbean->getName());

				for ($n = 0; $n < $formbean->getNumberOfNines(); $n++) {
					$nine = new NineDE($this->em);
					$nine->setName('Nine[' . $n . ']');
					$nine->setCourse($course);

					for ($t = 0; $t < $formbean->getNumberOfTees(); $t++) {
						$tee = new TeeDE($this->em);
						if ($n == 0) {
							$tee->setName('Tee[' . $t . ']');
						}
						$tee->setNine($nine);

						for ($h = 1; $h <= 9; $h++) {
							$hole = new HoleDE();
							if ($t == 0) {
								$hole->setName('Hole[' . $h . ']');
								$hole->setHolenumber($h);
							}
							$hole->setTee($tee);
							$tee->getHoles()->add($hole);
						}
						$nine->getTees()->add($tee);
					}
					$course->getNines()->add($nine);
				}
				$courseRepository->saveCourse($course);
				return $this->redirectToRoute('course_list');
			} else {
				$form->addError(new FormError('Cannot add a course with the same name to this database'));
			}
		}

		return $this->render('course/new.html.twig', [
			'title' => "New Course",
			'form' => $form->createView()
		]);
	}

	/**
	 * @param int $id
	 *
	 * @return Response
	 */
	#[Route('/course/view/{id}', name: 'course_view', methods: ['GET'])]
	public function view(int $id): Response {
		$courseRepository = new CourseRepository($this->em, $this->logger);
		$course = $courseRepository->find($id);

		return $this->render('course/view.html.twig', [
			'title' => 'Course',
			'course' => $course
		]);
	}
}
