<?php
namespace App\Repository;

use App\Entity\LeagueDE;
use App\Entity\SeasonDE;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the season table.
 */
class SeasonRepository extends AbstractBaseRepository {
	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, SeasonDE::class);
	}

    /**
     * Checks to make sure all season-required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $seasonData
     */
    protected function checkSeasonData(array &$seasonData): void {
        $seasonData['name'] ??= '';
        $seasonData['startDate'] ??= '';
        $seasonData['endDate'] ??= '';
    }

    /**
     * @param int $id of season
     *
     * @return SeasonDE|null
     */
    public function findById(int $id): ?SeasonDE {
        return $this->findOneBy(array('id' => $id));
    }

	/**
	 * @param int $leagueId
	 *
	 * @return mixed list of SeasonDEs for league specified
	 * @throws Exception
	 */
    public function findSeasonsByLeagueId(int $leagueId): mixed {
        try {
            // Crete QB instance and statement
            $qb = $this->createQueryBuilder('season');
            $qb->where($qb->expr()->eq('season.league', '?1'))
                ->orderBy('season.startdate', 'ASC')
                ->setParameter(1, $leagueId);

            // echo $qb->getQuery()->getSql();
            /** @noinspection PhpUnnecessaryLocalVariableInspection */
            $queryResult = $qb->getQuery()->getResult();
            return $queryResult;
        } catch (Exception $e) {
            throw $e;
        }
    }

	/**
	 * @param int $leagueId
	 * @param string $seasonName
	 *
	 * @return mixed SeasonDE
	 * @throws Exception
     * @noinspection PhpUnused
     */
    public function findSeasonByName(int $leagueId, string $seasonName): mixed {
        try {
            // get QB instance
            $qb = $this->createQueryBuilder('season');

            $expr = $qb->expr()->andX(
                $qb->expr()->eq('season.league', '?1'),
                $qb->expr()->like('season.name', '?2'));

            $qb->where($expr)
                ->setParameter(1, $leagueId)
                ->setParameter(2, $seasonName);

            /** @noinspection PhpUnnecessaryLocalVariableInspection */
            $queryResult = $qb->getQuery()->getResult();
            // echo $qb->getQuery()->getSql();
            return $queryResult;
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for season [%s]: %s',
		        'SeasonRepository::findSeasonByName', $seasonName, $e->getMessage()));
            throw $e;
        }
    }

    /**
     * Deletes a season entity
     *
     * @param SeasonDE $season
     *
     * @return SeasonDE
     * @throws Exception
     */
    public function removeSeason(SeasonDE $season): SeasonDE {
        try {
            $this->getEntityManager()->remove($season);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for season [%s]: %s',
		        'SeasonRepository::removeSeason', $season->getName(), $e->getMessage()));
            throw $e;
        }
        return $season;
    }

	/**
	 * Adds or updates season entity
	 *
	 * @param array $seasonData new or modified season data
	 * @param LeagueDE $league
	 * @param SeasonDE|null $season
	 *
	 * @return SeasonDE|null
	 * @throws Exception
	 */
    public function save(array $seasonData, LeagueDE $league, ?SeasonDE $season = NULL): ?SeasonDE {
        $this->checkSeasonData($seasonData);
        $season = $this->setSeasonData($seasonData, $league, $season);

        try {
            $this->getEntityManager()->persist($season);
            $this->getEntityManager()->flush();

            $sessionRepository = new SessionRepository($this->getEntityManager(), $this->getLogger());
            $sessionsData = $seasonData['session'];
            $sessions = $sessionRepository->saveAll($sessionsData, $season);
            $season->setSessions($sessions);
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for season [%s]: %s',
		        'SeasonRepository::save', $season->getName(), $e->getMessage()));
            throw $e;
        }
        return $season;
    }

	/**
	 * Adds all season entities
	 *
	 * @param array $seasonsData new or modified list of season data
	 *
	 * @return Collection of SeasonDEs
	 * @throws Exception
	 */
    public function saveAll(array $seasonsData, LeagueDE $league): Collection {
        $seasons = new ArrayCollection();

        foreach($seasonsData as $seasonData) {
            $season = $this->save($seasonData, $league);
            $seasons->add($season);
        }
        return $seasons;
    }

	/**
	 * Adds or updates season entity
	 *
	 * @param SeasonDE $season
	 *
	 * @return SeasonDE
	 * @throws Exception
	 */
    public function saveSeason(SeasonDE $season): SeasonDE {
        try {
            $this->getEntityManager()->persist($season);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for season [%s]: %s',
		        'SeasonRepository::saveSeason', $season->getName(), $e->getMessage()));
            throw $e;
        }
        return $season;
    }

	/**
	 * Calls setters to assign $seasonData to properties in $course
	 *
	 * @param array $seasonData
	 * @param LeagueDE $league
	 * @param ?SeasonDE $season
	 *
	 * @return SeasonDE|null $season
	 */
    protected function setSeasonData(array $seasonData, LeagueDE $league, ?SeasonDE $season = NULL): ?SeasonDE {
        if (!$season) {
            $season = new SeasonDE();
        }
        $season->setName($seasonData['name']);

        $format = "Y-m-d";
        $startDate = DateTime::createFromFormat($format, $seasonData['startDate']);
        $endDate = DateTime::createFromFormat($format, $seasonData['endDate']);
        $season->setStartdate($startDate);
        $season->setEnddate($endDate);

        $season->setLeague($league);

        return $season;
    }
}