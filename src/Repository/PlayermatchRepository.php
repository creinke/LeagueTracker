<?php
namespace App\Repository;

use App\Entity\PlayermatchDE;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the playermatch table.
 */
class PlayermatchRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, PlayermatchDE::class);
    }

	/**
	 * @param int $id of player
	 *
	 * @return object|NULL PlayermatchDE
	 * @throws Exception
	 */
    public function findOneByPlayerId( int $id) : ?PlayermatchDE {
        try {
            // get QB instance
            $qb = $this->createQueryBuilder('playermatch');
            $qb->setMaxResults(1);
            $qb->where($qb->expr()->orX($qb->expr()->eq('playermatch.playerone', ':id'), $qb->expr()->eq('playermatch.playertwo', ':id')));
            $qb->setParameters(array('id' => $id));

            $queryResult = $qb->getQuery()->getResult();
            // echo $qb->getQuery()->getSql();

            if (sizeof($queryResult) == 0) {
                return null;
            } else {
                return $queryResult[0];
            }
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for player match Id [%s]: %s',
		        'PlayermatchRepository::findOneByPlayerId', $id, $e->getMessage()));
            throw $e;
        }
    }

	/**
	 * @param int $id of score
	 *
	 * @return object|NULL PlayermatchDE
	 * @throws Exception
	 */
    public function findOneByScoreId( int $id) : ?PlayermatchDE {
        try {
            // get QB instance
            $qb = $this->createQueryBuilder('playermatch');
            $qb->setMaxResults(1);
            $qb->where($qb->expr()->orX($qb->expr()->eq('playermatch.playeronescore', ':id'), $qb->expr()->eq('playermatch.playertwoscore', ':id')));
            $qb->setParameters(array('id' => $id));

            $queryResult = $qb->getQuery()->getResult();
            // echo $qb->getQuery()->getSql();

            if (sizeof($queryResult) == 0) {
                return null;
            } else {
                return $queryResult[0];
            }
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for player match Id [%s]: %s',
		        'PlayermatchRepository::findOneByScoreId', $id, $e->getMessage()));
	        throw $e;
        }
    }

	/**
	 * Adds or updates a playermatch entity
	 *
	 * @param PlayermatchDE $playermatch
	 *
	 * @return PlayermatchDE
	 * @throws Exception
	 */
    public function savePlayermatch(PlayermatchDE $playermatch): PlayermatchDE {
        try {
            $this->getEntityManager()->persist($playermatch);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method: %s',
		        'PlayermatchRepository::savePlayermatch', $e->getMessage()));
	        throw $e;
        }
        return $playermatch;
    }
}
