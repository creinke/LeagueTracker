<?php
namespace App\Repository;

use App\Entity\AddressDE;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class where you can add "persist" and other specialized methods associated with the address table.
 */
class AddressRepository extends AbstractBaseRepository {

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		parent::__construct( $em, $logger, AddressDE::class );
	}

}