<?php
namespace App\Tests\Repository;

use App\Repository\EventRepository;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EventRepositoryTests extends KernelTestCase {
	private EntityManager $em;
	private LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();

		self::bootKernel();

		$this->em = self::getContainer()->get('doctrine.orm.entity_manager');
		$this->logger = self::getContainer()->get(LoggerInterface::class);
	}

	public function testRepositoryWorks(): void {
		$eventRepository = new EventRepository($this->em, $this->logger);
		$count = $eventRepository->count([]);

		$this->assertIsInt($count);
	}
}

