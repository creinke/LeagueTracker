<?php
namespace App\Model;

use Doctrine\ORM\Exception\ORMException;
use Exception;
use PDO;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;

class Init {
	const DB_USER   = 'creinke';
	const DB_PWD    = 'zMon@001ca';
	const DB_NAME   = 'premiergolfleaguetracker';
	const DB_HOST   = 'localhost';
	const DB_DRIVER = 'pdo_mysql';

	protected PDO $connection;

	protected EntityManager $entityManager;
	protected array $params;
	protected Configuration $config;

	/**
	 * Initializes database stuff
	 *
	 * @param array|null $params = if you need to override settings
	 *
	 * @throws ORMException
	 */
	public function __construct(array $params = null) {
		$default = [
			'entityDirs' => [
				__DIR__ . '/../Entity',
			],
			'db' => [
				'driver' => self::DB_DRIVER,
				'host' => self::DB_HOST,
				'dbname' => self::DB_NAME,
				'user' => self::DB_USER,
				'password'  => self::DB_PWD,
				'driver_options' => [
					PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
					PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
				]
			],
			'proxy' => [
				'autoGenerateProxy' => true,
				'proxyDir' => __DIR__ . '/../data/proxy',
			],
			'cache' => [
				'cacheAdapterClass' => 'FilesystemAdapter',
				'params' => [
					'namespace' => '',
					'defaultLifetime' => 0,
					'directory' => __DIR__ . '/../data/cache',
					'marshaller' => null
				]
			]
		];
		$this->setParams($default, $params);

		// ---- CACHE ----
		$cacheAdapterClass = 'Symfony\\Component\\Cache\\Adapter\\' . $this->params['cache']['cacheAdapterClass'];
		$cachePool = new $cacheAdapterClass(
			$this->params['cache']['params']['namespace'],
			$this->params['cache']['params']['defaultLifetime'],
			$this->params['cache']['params']['directory'],
			$this->params['cache']['params']['marshaller']
		);

		// ---- ATTRIBUTE DRIVER ----
		$attributeDriver = new AttributeDriver($this->params['entityDirs']);
		$this->config = Setup::createConfiguration(isDevMode: false);
		$this->config->setMetadataDriverImpl($attributeDriver);

		// ---- PROXY CONFIG ----
		$this->config->setProxyDir($this->params['proxy']['proxyDir']);
		$this->config->setAutoGenerateProxyClasses($this->params['proxy']['autoGenerateProxy']);

		// ---- CACHE CONFIG ----
		$this->config->setQueryCache($cachePool);
		$this->config->setMetadataCache($cachePool);

		// ---- ENTITY MANAGER ----
		$this->entityManager = EntityManager::create($this->params['db'], $this->config);

		// ---- PDO CONNECTION ----
		try {
			$dsn = 'mysql:dbname=' . self::DB_NAME . ';host=' . self::DB_HOST;
			$this->connection = new PDO($dsn, self::DB_USER, self::DB_PWD, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			]);
		} catch (Exception $e) {
			$this->handleError($e);
		}
	}

	public function getConnection(): PDO {
		return $this->connection;
	}

	public function getEntityManager(): EntityManager {
		return $this->entityManager;
	}

	public function setEntityManager($entityManager): void {
		$this->entityManager = $entityManager;
	}

	public function getParams(): array {
		return $this->params;
	}

	private function handleError(Exception $e) {
		// You can implement error handling/logging here.
	}

	public function setParams($params1, $params2 = null): void {
		$this->params = $params2 ? array_merge($params1, $params2) : $params1;
	}

	public function getConfig(): Configuration {
		return $this->config;
	}

	public function setConfig($config): void {
		$this->config = $config;
	}
}
