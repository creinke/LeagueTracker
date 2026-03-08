<?php
namespace App\Tests\Repository;

use App\Repository\RegionRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 *  Region Repository Test Case.
 */
class RegionRepositoryTest extends KernelTestCase  {
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

	public function testRegionRepositoryPopulation() {
	    $regionRepository = new RegionRepository($this->em, $this->logger, new ClassMetadata('Entity\RegionDE'));
		$regions = $regionRepository->findAll();
		
		echo 'Regions:' . PHP_EOL;
		
		foreach ($regions as $region) {
			$country = $region->getCountry()->getName();
			echo '    ' . $region->getName() . '(' . $region->getCode() . ':' . $region->getId() . ')' . PHP_EOL;
		}
		self::assertTrue(true);
	}
}

