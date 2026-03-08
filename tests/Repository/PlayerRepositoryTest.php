<?php
namespace App\Tests\Repository;

use App\Repository\LeagueRepository;
use App\Repository\PlayerRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PlayerRepositoryTest extends KernelTestCase {
	private EntityManager $em;
	private LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();

		self::bootKernel();

		$this->em = self::getContainer()->get('doctrine.orm.entity_manager');
		$this->logger = self::getContainer()->get(LoggerInterface::class);
		$this->logger->debug('✅ Static logger test');
		$logger = self::getContainer()->get('logger');
		self::getContainer()->get('monolog.logger.deprecation')->debug('⚠️ Deprecation log');
		self::getContainer()->get('monolog.logger.event')->debug('📣 Event log');
		self::getContainer()->get('logger')->debug('✅ Static logger test');
		// self::getContainer()->get('monolog.logger.app')->debug('🟢 App log');
		//dd(get_class($logger), $logger->getHandlers());
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() : void {
	}

	/**
	 * @throws Exception
	 */
	public function testFindPlayerByName() {
		$playerRepository = new PlayerRepository($this->em, $this->logger);
		$leagueRepository = new LeagueRepository($this->em, $this->logger);
		$leagueName = "SOLMIS Golf League";
		$league = $leagueRepository->findLeagueByName($leagueName);

		$name = "Kurt Reinke";
		echo PHP_EOL . 'Player: ' . $name . PHP_EOL;
		$startingdateandtime = new DateTime();

		$queryResult = $playerRepository->findPlayerByNameString($league->getId(), $name);
		$player = $queryResult[0];

		if (empty($player)) {
			$this->logger->debug('findPlayerByName', ['league name' => $leagueName, 'player name' => $name ]);
			throw new RuntimeException('findPlayerByName: ' . $name . ' not found', 0, null);
		}

		echo '    Player Data:' . $player->getName()->getFullName() . PHP_EOL;

		self::assertTrue(true);
	}
}

