<?php
namespace App\Tests\Repository;

use App\Repository\EventRepository;
use App\View\TeamEventViewBean;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 *  Event Repository Test Case.
 */
class EventRepositoryTest extends KernelTestCase  {
	private EntityManager $em;
	private LoggerInterface $logger;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() : void {
		parent::setUp();

		self::bootKernel();
		$this->em = self::getContainer()->get('doctrine.orm.entity_manager');
		$this->logger = self::getContainer()->get(\Psr\Log\LoggerInterface::class);
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() : void {
	}

	public function testFixup() {
	    $eventRepository = new EventRepository($this->em, $this->logger);
	    
	    $events = $eventRepository->findAll();
	    
	    foreach($events as $event) {
	        if ($event->getFormat() == 2 || $event->getFormat() == 0) {
	            $event->setFormat(1);
	            // $eventRepository->saveEvent($event);
	        }
	    }
		self::assertTrue(true);
	}

    /**
     * @throws \Exception
     */
    public function testPopulateTeamEventView() {
	    $eventRepository = new EventRepository($this->em, $this->logger);
	    $event = $eventRepository->find(318);
	    
	    $season = $event->getSession()->getSeason();
	    $teamViewBean = new TeamEventViewBean($event, $this->em, $this->logger);
	    $teamViewBean->calculateEventResults($event);
	    
	    self::assertTrue(true);
	}
	
	public function testEventRepositorySecondNineColumnAddition() {
	    $eventRepository = new EventRepository($this->em, $this->logger, new ClassMetadata('Entity\EventDE'));
		echo PHP_EOL;
		
		for ($id = 310; $id < 326; $id++) {
			$event = $eventRepository->find($id);
			
			// echo 'query[' . $this->logger->currentQuery . ']:' . PHP_EOL;
			// echo var_dump($this->logger->queries[$this->logger->currentQuery]);
			
			$nine = $event->getNine();
			$secondNine = $event->getSecondnine();
			
			echo "Event $id:" . PHP_EOL;
			
			if (!empty($nine) && !empty($secondNine)) {
				echo '    ' . 'Front Nine: ' . $nine->getName() . ', Back Nine: ' . $secondNine->getName() . PHP_EOL;
			} else if (!empty($nine)) {
				echo '    ' . 'Front Nine: ' . $nine->getName() . ', No Back Nine' . PHP_EOL;
			} else if (!empty($nine)) {
				echo '    ' . 'Back Nine: ' . $secondNine->getName() . ', No Front Nine'. PHP_EOL;
			}
		}
		self::assertTrue(true);
	}
}

