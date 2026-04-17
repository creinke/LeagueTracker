<?php
namespace App\Repository;

use App\Entity\TeamgameDE;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the team game table.
 */
class TeamgameRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, TeamgameDE::class);
    }

	/**
	 * Deletes a team game entity
	 *
	 * @param TeamgameDE $teamgame
	 *
	 * @return TeamgameDE
	 * @throws Exception
	 */
    public function removeTeamgame(TeamgameDE $teamgame): TeamgameDE {
        try {
            $this->getEntityManager()->remove($teamgame);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for Team Game Id [%s]: %s',
		        'TeamgameRepository::removeTeamgame', $teamgame->getId(), $e->getMessage()));
            throw $e;
        }
        return $teamgame;
    }

	/**
	 * Adds or updates team game entity
	 *
	 * @param TeamgameDE $teamgame
	 *
	 * @return TeamgameDE
	 * @throws Exception
	 */
    public function saveTeamgame(TeamgameDE $teamgame): TeamgameDE {
        try {
            $this->getEntityManager()->persist($teamgame);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method: %s',
		        'TeamgameRepository::saveTeamgame', $e->getMessage()));
            throw $e;
        }
        return $teamgame;
    }
}