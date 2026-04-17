<?php
namespace App\Repository;

use App\Entity\CountryDE;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the country table.
 */
class CountryRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, CountryDE::class);
    }

	/**
	 * Checks to make sure all country-required fields are set
	 * This is also where to perform secondary filtering/sanitization of data
	 *
	 * @param array $countryData reference $countryData
	 */
	protected function checkCountryData(array &$countryData): void {
		$countryData['iso3'] ??= '';
	}

	/**
     * @param string $name of country
     *
     * @return ?CountryDE
     */
    public function findCountryByName(string $name) : ?CountryDE {
        return $this->findOneBy(array('name' => $name));
    }

	/**
	 * Adds or updates country entity
	 *
	 * @param array $countryData new or modified country data
	 *
	 * @return CountryDE
	 * @throws Exception
	 */
	public function save(array $countryData): CountryDE {
		$country = $this->findCountryByName($countryData['iso3']);

		if ($country) {
			// There should be no duplicate country names
			return $country;
		}

		$this->checkCountryData($countryData);
		$country = $this->setCountryData($countryData);

		try {
			$this->getEntityManager()->persist($country);
			$this->getEntityManager()->flush();
		} catch (Exception $e) {
			$this->logError(sprintf('Error in the %s method for country [%s]: %s',
				'countryRepository::save', $country->getName(), $e->getMessage()));
			throw $e;
		}
		return $country;
	}

	/**
	 * Adds all country entities
	 *
	 * @param array $countriesData new or modified list of country data
	 *
	 * @return Collection of Entity\CountryDE
	 * @throws Exception
	 */
	public function saveAll(array $countriesData): Collection {
		$countries = new ArrayCollection();

		foreach($countriesData as $countryData) {
			$country = $this->save($countryData);
			$countries->add($country);
		}
		return $countries;
	}

	/**
	 * Calls setters to assign $countryData to properties in $country
	 *
	 * @param array $countryData
	 *
	 * @return CountryDE $country
	 */
	protected function setCountryData(array $countryData): CountryDE {
		$country = new CountryDE();
		$country->setName($countryData['iso3']);
		return $country;
	}
}