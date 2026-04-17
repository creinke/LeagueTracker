<?php
namespace App\Repository;

use App\Entity\SeasonDE;
use App\Entity\SessionDE;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the session table.
 */
class SessionRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, SessionDE::class);
    }

    /**
     * Checks to make sure all session-required fields are set
     * This is also where to perform secondary filtering/sanitization of data
     *
     * @param array $sessionData
     */
    protected function checkSessionData(array &$sessionData): void {
        $sessionData['name'] ??= '';
        $sessionData['startDate'] ??= '';
        $sessionData['endDate'] ??= '';
    }

    /**
     * @param int $id of session
     *
     * @return SessionDE
     */
    public function findById(int $id): SessionDE {
        return $this->findOneBy(array('id' => $id));
    }

	/**
	 * @param int $seasonId
	 *
	 * @return mixed SessionDEs for season specified
	 * @throws Exception
	 */
    public function findSessionsBySeasonId(int $seasonId): mixed {
        try {
            // Crete QB instance and statement
            $qb = $this->createQueryBuilder('session');
            $qb->where($qb->expr()->eq('session.season', '?1'))
            ->orderBy('session.startdate', 'ASC')
            ->setParameter(1, $seasonId);
            
            // echo $qb->getQuery()->getSql();
            /** @noinspection PhpUnnecessaryLocalVariableInspection */
            $queryResult = $qb->getQuery()->getResult();
            return $queryResult;
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for course Id [%s]: %s',
		        'SessionRepository::findSessionsBySeasonId', $seasonId, $e->getMessage()));
            throw $e;
        }
    }

    /**
     * Deletes a session entity
     *
     * @param SessionDE $session
     *
     * @return SessionDE
     * @throws Exception
     */
	public function removeSession(SessionDE $session): SessionDE {
		try {
			$this->getEntityManager()->remove($session);
			$this->getEntityManager()->flush();
		} catch (Exception $e) {
			$this->logError(sprintf('Error in the %s method for session [%s]: %s',
				'SessionRepository::removeSession', $session->getName(), $e->getMessage()));
			throw $e;
		}
		return $session;
	}

	/**
	 * Adds or updates session entity
	 *
	 * @param array $sessionData new or modified session data
	 * @param SeasonDE $season
	 * @param SessionDE|null $session
	 *
	 * @return SessionDE
	 * @throws Exception
	 */
    public function save(array $sessionData, SeasonDE $season, ?SessionDE $session = NULL): SessionDE {
        $this->checkSessionData($sessionData);
        $session = $this->setSessionData($sessionData, $season, $session);

        try {
            $this->getEntityManager()->persist($session);
            $this->getEntityManager()->flush();

            $eventRepository = new EventRepository($this->getEntityManager(), $this->getLogger());
            $eventsData = $sessionData['event'];
            $events = $eventRepository->saveAll($eventsData, $session);
            $session->setEvents($events);
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method: %s',
		        'SessionRepository::save', $e->getMessage()));
            throw $e;
        }
        return $session;
    }

	/**
	 * Adds all session entities
	 *
	 * @param array $sessionsData new or modified list of session data
	 *
	 * @return Collection of SessionDE
	 * @throws Exception
	 */
    public function saveAll(array $sessionsData, SeasonDE $season): Collection {
        $sessions = new ArrayCollection();

        foreach($sessionsData as $sessionData) {
            $session = $this->save($sessionData, $season);
            $sessions->add($session);
        }
        return $sessions;
    }

	/**
	 * Adds or updates session entity
	 *
	 * @param SessionDE $session
	 *
	 * @return SessionDE
	 * @throws Exception
	 */
    public function saveSession(SessionDE $session): SessionDE {
        try {
            $this->getEntityManager()->persist($session);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method: %s',
		        'SessionRepository::saveSession', $e->getMessage()));
            throw $e;
        }
        return $session;
    }
    
    /**
     * Calls setters to assign $sessionData to properties in $course
     *
     * @param array $sessionData
     * @param SeasonDE $season
     * @param ?SessionDE $session
     *
     * @return SessionDE $session
     */
    protected function setSessionData(array $sessionData, SeasonDE $season, ?SessionDE $session = NULL): SessionDE {
        $session ??= new SessionDE();

        $session->setName($sessionData['name']);

        $format = "Y-m-d";
        $startDate = DateTime::createFromFormat($format, $sessionData['startDate']);
        $endDate = DateTime::createFromFormat($format, $sessionData['endDate']);
        $session->setStartdate($startDate);
        $session->setEnddate($endDate);

        $session->setSeason($season);

        return $session;
    }
}