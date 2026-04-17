<?php
namespace App\Repository;

use App\Entity\TeamgameplayerDE;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the team player table.
 */
class TeamgameplayerRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, TeamgameplayerDE::class);
    }

	/**
	 * Deletes a team player entity
	 *
	 * @param TeamgameplayerDE $teamgameplayer
	 *
	 * @return TeamgameplayerDE
	 * @throws Exception
     * @noinspection PhpUnused
     */
    public function removeTeamGamePlayer(TeamgameplayerDE $teamgameplayer): TeamgameplayerDE {
        try {
            $this->getEntityManager()->remove($teamgameplayer);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for Team Game Player Id [%s]: %s',
		        'TeamgameplayerRepository::removeTeamGamePlayer', $teamgameplayer->getId(), $e->getMessage()));
            throw $e;
        }
        return $teamgameplayer;
    }

	/**
	 * Adds or updates team player entity
	 *
	 * @param TeamgameplayerDE $teamgameplayer
	 *
	 * @return TeamgameplayerDE
	 * @throws Exception
     * @noinspection PhpUnused
     */
    public function saveTeamGamepPayer(TeamgameplayerDE $teamgameplayer): TeamgameplayerDE {
        try {
            $this->getEntityManager()->persist($teamgameplayer);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method: %s',
		        'TeamgameplayerRepository::saveTeamGamepPayer', $e->getMessage()));
            throw $e;
        }
        return $teamgameplayer;
    }
}