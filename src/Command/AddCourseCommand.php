<?php
namespace App\Command;

use App\Entity\AddressDE;
use App\Entity\CourseDE;
use App\Entity\HoleDE;
use App\Entity\NineDE;
use App\Entity\TeeDE;
use App\Repository\CourseRepository;
use App\Repository\RegionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument as InputArgumentAlias;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'app:add-course',
	description: 'Imports a new course from a CSV formatted file'
)]
class AddCourseCommand extends Command {
	public function __construct(private readonly EntityManagerInterface $em, private readonly LoggerInterface $logger) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('app:add-course');
		$this->addArgument('csvDataFile', InputArgumentAlias::REQUIRED, 'Course CSV Data File');
	}

	/**
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int	{
        /** @noinspection DuplicatedCode */
        $appEnv = $_ENV['APP_ENV'] ?? ($_SERVER['APP_ENV'] ?? null);
		$databaseUrl = $_ENV['DATABASE_URL'] ?? ($_SERVER['DATABASE_URL'] ?? null);

		// Print these out so that you know what environment the application is running in.
		$output->writeln('APP_ENV: '.($appEnv ?? '(not set)'));
		$output->writeln('DATABASE_URL: '.($databaseUrl ?? '(not set)'));

		if ($input->getArguments() < 2 ) {
			exit( "Usage: add-course <course CSV file name>\n" );
		} else {
            $courseName = $input->getArgument('csvDataFile');
		}
        if (!($handle = fopen(__DIR__ . '/../../data/' . $courseName . '.csv', "r"))) {
            exit ('Unable to open CSV file ./data/' . $courseName . '.csv');
        }

        $nines = new ArrayCollection();
        $tees = new ArrayCollection();
        $holes = new ArrayCollection();

        /** @noinspection PhpRedundantOptionalArgumentInspection */
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($data[0] == 'course') {
                $course = new CourseDE();
                $course->setName($data[1]);
                $course->setWebsite($data[2]);

                $address = new AddressDE();
                $address->setAddressline1($data[3]);
                $address->setAddressline2($data[4]);
                $address->setCity($data[5]);

                $regionRepository = new RegionRepository($this->em, $this->logger);
                $region = $regionRepository->findOneBy(array('code' => $data[6]));
                $address->setRegion($region);

                $address->setPostalcode($data[8]);
                $course->setAddress($address);
            } else if ($data[0] == 'endcourse') {
                /** @noinspection PhpUndefinedVariableInspection */
                $course->setNines($nines);
                $courseRepository = new CourseRepository($this->em, $this->logger);
                $courseRepository->saveCourse($course);
            } else if ($data[0] == 'nine') {
                $nine = new NineDE();
                $nine->setName($data[1]);
                /** @noinspection PhpUndefinedVariableInspection */
                $nine->setCourse($course);
                $nines->add($nine);
            } else if ($data[0] == 'endnine') {
                /** @noinspection PhpUndefinedVariableInspection */
                $nine->setTees($tees);
            } else if ($data[0] == 'tee') {
                $tee = new TeeDE();
                $tee->setName($data[1]);
                $tee->setPar($data[2]);
                $tee->setRating($data[3]);
                $tee->setLength($data[4]);
                $tee->setSlope($data[5]);
                /** @noinspection PhpUndefinedVariableInspection */
                $tee->setNine($nine);
                $tees->add($tee);
            } else if ($data[0] == 'endtee') {
                /** @noinspection PhpUndefinedVariableInspection */
                $tee->setHoles($holes);
            } else if ($data[0] == 'hole') {
                $hole = new HoleDE();
                $holeNumber = $data[1];
                $hole->setHolenumber($holeNumber);
                $hole->setName('hole[' . $holeNumber . ']');
                $hole->setPar($data[2]);
                $hole->setHandicap($data[3]);
                $hole->setLength($data[4]);
                /** @noinspection PhpUndefinedVariableInspection */
                $hole->setTee($tee);
                $holes->add($hole);
            }
        }
        fclose($handle);

		$output->writeln('Course Loader complete.');
		return Command::SUCCESS;
	}
}
