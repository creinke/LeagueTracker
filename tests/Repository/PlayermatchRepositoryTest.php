<?php
namespace App\Tests\Repository;

use App\Entity\ScoreDE;
use App\Repository\PlayermatchRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 *  Playermatch Repository Test Case.
 */
class PlayermatchRepositoryTest extends KernelTestCase  {
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

	public function testPlayermatchRepositoryPopulation() {
	    $playermatchRepository = new PlayermatchRepository($this->em, $this->logger, new ClassMetadata('Entity\PlayermatchDE'));
		$playermatches = $playermatchRepository->findAll();
		
		echo 'Player Matches:' . PHP_EOL;
		
		foreach ($playermatches as $playermatch) {
			if ($playermatch->getPlayerscores()->count() > 0) {
				$playerOne = $playermatch->getPlayerone();
				$playerOneName = $playerOne->getName()->getFullname();
				$playerOneScores = $playermatch->getPlayerOneScores();
				
				$playerTwo = $playermatch->getPlayertwo();
				$playerTwoName = $playerTwo->getName()->getFullname();
				$playerTwoScores = $playermatch->getPlayerTwoScores();
				
				echo '    ' . $playerOneName . ' vs ' . $playerTwoName . PHP_EOL;
				
				if (!is_null($playerOneScores)) {
					foreach ($playerOneScores as $playerScore) {
						echo '    ';
						$strokes = ScoreDE::unpack( $playerScore->getStrokes() );

						for ( $hole = 0; $hole < 9; $hole ++ ) {
							echo $strokes[ $hole ];

							if ( $hole < 8 ) {
								echo ', ';
							}
						}
						echo PHP_EOL;
					}
				}
				
				if (!is_null($playerTwoScores)) {
					foreach ( $playerTwoScores as $playerScore ) {
						echo '    ';
						$strokes = ScoreDE::unpack( $playerScore->getStrokes() );

						for ( $hole = 0; $hole < 9; $hole ++ ) {
							echo $strokes[ $hole ];

							if ( $hole < 8 ) {
								echo ', ';
							}
						}
						echo PHP_EOL;
					}
				}
			}
		}
		self::assertTrue(true);
	}
}

