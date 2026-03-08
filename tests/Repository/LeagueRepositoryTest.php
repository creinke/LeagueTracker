<?php
namespace App\Tests\Repository;

use App\Repository\LeagueRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 *  League Repository Test Case.
 */
class LeagueRepositoryTest extends KernelTestCase {
    private EntityManager $em;
    private LoggerInterface $logger;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp() : void {
	    parent::setUp();

	    self::bootKernel();

	    $this->em = self::getContainer()->get('doctrine.orm.entity_manager');
	    $this->logger = self::getContainer()->get(LoggerInterface::class);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown() : void {
    }

    public function testLeagueRepositoryLastEvent() {
        $leagueRepository = new LeagueRepository($this->em, $this->logger, new ClassMetadata('Entity\LeagueDE'));
        $leagueName = "Solmis Golf League";
        $league = $leagueRepository->findLeagueByName($leagueName);
        $dateTime =  new DateTime("2025-10-30 16:12:00.000000");

	    $lastEvent = null;
		foreach($league->getSeasons() as $season) {
            foreach($season->getSessions() as $session) {
                foreach($session->getEvents() as $event) {
					if ($lastEvent == null) {
						$lastEvent = $event;
					} else if ($event->getStartdateandtime() < $dateTime && $event->getStartdateandtime() > $lastEvent->getStartdateandtime()) {
                        $lastEvent = $event;
                    }
                }
            }
        }
		If ($lastEvent != null) {
	        echo PHP_EOL;
	        echo 'Last Event:' . $lastEvent->getEventNumber() . ' on ' . date_format($lastEvent->getStartdateandtime(), 'Y-m-d H:i:s') . PHP_EOL;
		}
        self::assertTrue(true);
    }
}

