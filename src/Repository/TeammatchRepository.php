<?php
namespace App\Repository;

use App\Entity\TeammatchDE;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the teammatch table.
 */
class TeammatchRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct($em, $logger, TeammatchDE::class);
    }

	/**
	 * @param int $id of team
	 *
	 * @return TeammatchDE|null
	 * @throws Exception
	 */
    public function findOneByTeamId(int $id) : ?TeammatchDE {
        try {
            // get QB instance
            $qb = $this->createQueryBuilder('teammatch');
            $qb->setMaxResults(1);
            $qb->where($qb->expr()->orX($qb->expr()->eq('teammatch.teamone', ':id'), $qb->expr()->eq('teammatch.teamtwo', ':id')));
            $qb->setParameters(array('id' => $id));

            $queryResult = $qb->getQuery()->getResult();
            // echo $qb->getQuery()->getSql();

            if (sizeof($queryResult) == 0) {
                return null;
            } else {
                return $queryResult[0];
            }
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method for Team Match Id [%s]: %s',
		        'TeammatchRepository::findOneByTeamId', $id, $e->getMessage()));
            throw $e;
        }
    }

	/**
	 * Adds or updates a teammatch entity
	 *
	 * @param TeammatchDE $teammatch
	 *
	 * @return TeammatchDE
	 * @throws Exception
	 */
    public function saveTeammatch(TeammatchDE $teammatch): TeammatchDE {
        try {
            $this->getEntityManager()->persist($teammatch);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
	        $this->logError(sprintf('Error in the %s method: %s',
		        'TeammatchRepository::saveTeammatch', $e->getMessage()));
            throw $e;
        }
        return $teammatch;
    }
}
