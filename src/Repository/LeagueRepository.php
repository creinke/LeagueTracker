<?php
namespace App\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use App\Entity\LeagueDE;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the league table.
 */
class LeagueRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, LeagueDE::class);
    }

    /**
     * Checks to make sure all league-required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $leagueData
     */
    protected function checkLeagueData(array &$leagueData): void {
        $leagueData['name'] ??= 'League';
        $leagueData['courses'] ??= new PersistentCollection($this->getEntityManager(), new ClassMetadata('App\Entity\CourseDE'), new ArrayCollection());
    }

	/**
	 * @throws \Doctrine\DBAL\Exception
	 */
	private function executeLeagueQuery(string $leagueName): array {
		$connection = $this->getEntityManager()->getConnection();
		$platform = $connection->getDatabasePlatform();

		$qb = $connection->createQueryBuilder();
		$qb->select($platform->quoteIdentifier('id'))
		    ->from($platform->quoteIdentifier('league'), 'league')
		    ->where($platform->quoteIdentifier('league.name') . ' = :name')
		    ->setParameter('name', $leagueName);

		return $qb->executeQuery()->fetchAllAssociative();
	}

	/**
	 * Find the League Entity by id.
	 *
	 * @param int $id of league
	 *
	 * @return LeagueDE LeagueDE
	 */
    public function findById(int $id) : LeagueDE {
        return $this->findOneBy(array('id' => $id));
    }

    /**
     * Find the League Entity by name.
     *
     * @param string $name of league
     * @return object|NULL LeagueDE
     */
    public function findLeagueByName(string $name) : ?LeagueDE {
        return $this->findOneBy(array('name' => $name));
    }

	/**
	 * @param string $leagueName
	 *
	 * @return int|null leagueId
	 * @throws Exception
	 */
	public function findLeagueIdByName(string $leagueName): ?int {
		try {
			$result = $this->executeLeagueQuery($leagueName);
			return $result[0]['id'] ?? null;
		} catch (Exception $e) {
			$this->logError(sprintf('Error in the %s method for league [%s]: %s',
				'LeagueRepository::findLeagueIdByName', $leagueName, $e->getMessage()));
			throw $e;
		}
	}

	/**
	 * Deletes a league entity
	 *
	 * @param LeagueDE $league
	 *
	 * @return LeagueDE
	 * @throws Exception
	 */
    public function removeLeague(LeagueDE $league): LeagueDE {
        try {
            $this->getEntityManager()->remove($league);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for league [%s]: %s',
		        'LeagueRepository::removeLeague', $league->getName(), $e->getMessage()));
	        throw $e;
        }
        return $league;
    }

	/**
	 * Adds or updates League Entity
	 *
	 * @param array $leagueData new or modified league data
	 * @param LeagueDE|null $league
	 *
	 * @return LeagueDE
	 * @throws Exception
	 */
    public function save(array $leagueData, LeagueDE $league = NULL) : LeagueDE {
        $this->checkLeagueData($leagueData);
        $league = $this->setLeagueData($leagueData, $league);

        try {
            $this->getEntityManager()->persist($league);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for league [%s]: %s',
		        'LeagueRepository::save', $league->getName(), $e->getMessage()));
	        throw $e;
        }
        return $league;
    }

    /**
     * @param LeagueDE $league
     * @throws Exception
     * @return LeagueDE
     */
    public function saveLeague(LeagueDE $league): LeagueDE {
        try {
            $this->getEntityManager()->persist($league);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for league [%s]: %s',
		        'LeagueRepository::saveLeague', $league->getName(), $e->getMessage()));
	        throw $e;
        }
        return $league;
    }

	/**
	 * Calls setters to assign $leagueData to properties in $course
	 *
	 * @param array $leagueData
	 * @param LeagueDE|null $league
	 *
	 * @return LeagueDE $league
	 */
    protected function setLeagueData(array $leagueData, LeagueDE $league = NULL): LeagueDE {
        $league ??= new LeagueDE($this->getEntityManager());

        $league->setName($leagueData['name']);
        $league->setCourses($leagueData['courses']);

        return $league;
    }
}