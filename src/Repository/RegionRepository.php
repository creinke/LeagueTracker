<?php
namespace App\Repository;

use App\Entity\CountryDE;
use App\Entity\RegionDE;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the region table.
 */
class RegionRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, RegionDE::class);
    }

	/**
	 * Checks to make sure all region-required fields are set
	 * This is also where to perform secondary filtering/sanitization of data
	 *
	 * @param array $regionData reference $regionData
	 */
	protected function checkRegionData(array &$regionData): void {
		$regionData['name'] ??= '';
		$regionData['code'] ??= '';
	}

    /**
     * @param string $code
     * @param CountryDE $country
     *
     * @return ?RegionDE
     */
    public function findRegionByCode( string $code, CountryDE $country) : ?RegionDE {
        return $this->findOneBy(array('code' => $code, 'country' => $country));
    }

    /**
     * @param string $name
     * @param CountryDE $country
     *
     * @return ?RegionDE
     * @noinspection PhpUnused
     */
    public function findRegionByName(string $name, CountryDE $country) : ?RegionDE {
        return $this->findOneBy(array('name' => $name, 'country' => $country));
    }

	/**
	 * Adds or updates region entity
	 *
	 * @param CountryDE $country associated with region
	 * @param array $regionData new or modified region data
	 *
	 * @return RegionDE
	 * @throws Exception
	 */
	public function save(CountryDE $country, array $regionData): RegionDE {
		$region = $this->findRegionByCode($regionData['code'], $country);

		if ($region) {
			// There should be no duplicate region names
			return $region;
		}

		$this->checkRegionData($regionData);
		$region = $this->setRegionData($country, $regionData);

		try {
			$this->getEntityManager()->persist($region);
			$this->getEntityManager()->flush();
		} catch (Exception $e) {
			$this->logError(sprintf('Error in the %s method for region [%s]: %s',
				'regionRepository::save', $region->getName(), $e->getMessage()));
			throw $e;
		}
		return $region;
	}

	/**
	 * Adds all region entities
	 *
	 * @param array $regionsData new or modified list of region data
	 *
	 * @return Collection of Entity\RegionDE
	 * @throws Exception
	 */
	public function saveAll(CountryDE $country, array $regionsData): Collection {
		$regions = new ArrayCollection();

		foreach($regionsData as $regionData) {
			$region = $this->save($country, $regionData);
			$regions->add($region);
		}
		return $regions;
	}

	/**
	 * Calls setters to assign $regionData to properties in $region
	 *
	 * @param CountryDE $country associated with region
	 * @param array $regionData
	 *
	 * @return RegionDE $region
	 */
	protected function setRegionData(CountryDE $country, array $regionData): RegionDE {
		$region = new RegionDE();

		$region->setCountry($country);
		$region->setName($regionData['name']);
		$region->setCode($regionData['code']);

		return $region;
	}
}