<?php
namespace App\Tests\Repository;

use App\Repository\LeagueRepository;
use App\Repository\PlayerRepository;
use App\Repository\ScoreRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ScoreRepositoryTest extends KernelTestCase {
	private EntityManager $em;
	private LoggerInterface $logger;

	protected function setUp(): void {
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

	/**
	 * @throws \Exception
	 */
	public function testCalculatePlayerHandicapIndex() {
		$this->logger->debug('Starting testCalculatePlayerHandicapIndex() test!');

	    $playerRepository = new PlayerRepository($this->em, $this->logger);
	    $scoreRepository = new ScoreRepository($this->em, $this->logger);
	    $leagueRepository = new LeagueRepository($this->em, $this->logger);

	    $leagueName = "SOLMIS Golf League";
	    $league = $leagueRepository->findLeagueByName($leagueName);

	    $name = "David Isaacs";
	    echo PHP_EOL . 'Player: ' . $name . PHP_EOL;
	    $startingdateandtime = new DateTime();
	    
	    $queryResult = $playerRepository->findPlayerByNameString($league->getId(), $name);
	    $player = $queryResult[0];
	    
	    $scores = $scoreRepository->findPlayerScores($player, $startingdateandtime);
	    $scoresRecorded = sizeOf($scores);
	    
	    for ($scoreIndex = $scoresRecorded - 1; $scoreIndex >= 0; $scoreIndex--) {
	        $s = array();
	        $score = $scores[$scoreIndex];
	        
	        for ($x = $scoreIndex; $x < $scoresRecorded && $x < $scoreIndex + 20; $x++) {
	            $s[] = $scores[$x];
	        }
	        $handicapDifferential = $score->calculateHandicapDifferential(sizeof($s));
	        $score->setHandicapDifferential($handicapDifferential);  
	        echo '    Handicap Differential for Score[' . $scoreIndex . ']:' . $handicapDifferential . PHP_EOL;
	        
	        $result = $scoreRepository->calculatePlayerHandicapIndex($player, $startingdateandtime, $s);
	        $handicapIndex = $result['currentHandicapIndex'];
	        
	        echo '        Handicap Index after score ' . ($scoresRecorded - $scoreIndex) . ' is: ' . $handicapIndex . PHP_EOL;
	    }
	    $result = $scoreRepository->calculatePlayerHandicapIndex($player, $startingdateandtime, $scores);
	    $handicapIndex = $result['currentHandicapIndex'];
	    
	    echo '    Current Handicap Index:' . $handicapIndex . PHP_EOL;
	    
	    self::assertTrue($handicapIndex > 0);
	}
}

