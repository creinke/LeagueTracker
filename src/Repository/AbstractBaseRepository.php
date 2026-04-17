<?php
namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractBaseRepository extends EntityRepository {
	protected LoggerInterface $logger;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger, string $entityClass) {
		parent::__construct($em, $em->getClassMetadata($entityClass));
		$this->logger = $logger;
	}

	protected function logDebug(string $message, array $context = []): void {
		$this->logger->debug($message, $context);
	}

	protected function logError(string $message, array $context = []): void {
		$this->logger->error($message, $context);
	}

    /** @noinspection PhpUnused */
    protected function logInfo(string $message, array $context = []): void {
		$this->logger->info($message, $context);
	}

    /** @noinspection PhpUnused */
    protected function logWarning(string $message, array $context = []): void {
		$this->logger->warning($message, $context);
	}

	public function getLogger(): LoggerInterface {
		return $this->logger;
	}

	public function setLogger(LoggerInterface $logger): void {
		$this->logger = $logger;
	}
}