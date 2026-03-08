<?php
namespace App\Model;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\Setup;
use stdClass;

class Base {
	const CACHE_NAMESPACE = 'Doctrine\Common\Cache\\';

	protected mixed $cache;
	protected EntityManager $em;
	protected array $params;
	protected EventManager $eventManager;
	protected Configuration $config;

	public function __construct($params) {
		// cache
		$this->cache = new stdClass();
		if (isset($params['cache']['class'])) {
			$this->cache = $this->buildCache($params['cache']['class'], $params['cache']['params']);
		}

		// AttributeDriver instead of AnnotationDriver
		$this->config = Setup::createConfiguration(isDevMode: true);
		$attributeDriver = new AttributeDriver($params['entityDirs']);
		$this->config->setMetadataDriverImpl($attributeDriver);

		// Proxy Configuration
		$this->config->setProxyDir($params['proxy']['proxyDir']);
		$this->config->setAutoGenerateProxyClasses($params['proxy']['autoGenerateProxy']);

		// Create EntityManager
		if (isset($params['event-manager'])) {
			$this->em = EntityManager::create($params['db'], $this->config, $params['event-manager']);
		} else {
			$this->em = EntityManager::create($params['db'], $this->config, new EventManager());
		}
	}

	protected function buildCache($target, $params) {
		$class = self::CACHE_NAMESPACE . $target;

		switch ($target) {
			case 'ArrayCache':
			case 'ApcCache':
			case 'WinCacheCache':
			case 'XcacheCache':
			case 'ZendDataCache':
				$cache = new $class();
				break;

			case 'PhpFileCache':
			case 'FilesystemCache':
				$cache = new $class($params['dir'], $params['ext']);
				break;

			case 'MongoDBCache':
				$collection = $params['collection']();
				$cache = new $class($collection);
				break;

			case 'PredisCache':
				$cache = new $class($params['client']);
				break;

			case 'RiakCache':
				$cache = new $class($params['bucket']);
				break;

			case 'SQLite3Cache':
				$cache = new $class($params['sqlite'], $params['table']);
				break;

			case 'Memcache':
				$driver = self::CACHE_NAMESPACE . $params['driver'];
				$temp = new $class();
				$temp->connect($params['host'], $params['port']);
				$cache = new $driver();
				$cache->setMemcache($temp);
				break;

			case 'Memcached':
				$driver = self::CACHE_NAMESPACE . $params['driver'];
				$temp = new $class();
				$temp->addServer($params['host'], $params['port']);
				$cache = new $driver();
				$cache->setMemcached($temp);
				break;

			case 'RedisCache':
				$driver = self::CACHE_NAMESPACE . $params['driver'];
				$temp = new $class();
				$temp->connect($params['host'], $params['port']);
				$cache = new $driver();
				$cache->setRedis($temp);
				break;

			default:
				$cache = new $class();
		}

		return $cache;
	}

	public function getCache() {
		return $this->cache;
	}

	public function setCache($cache): void {
		$this->cache = $cache;
	}

	public function getEm(): EntityManager {
		return $this->em;
	}

	public function setEm($em): void {
		$this->em = $em;
	}

	public function getParams(): array {
		return $this->params;
	}

	public function setParams($params): void {
		$this->params = $params;
	}

	public function getEventManager(): EventManager {
		if (!$this->eventManager) {
			$this->eventManager = new EventManager();
		}
		return $this->eventManager;
	}

	public function getConfig(): Configuration {
		return $this->config;
	}

	public function setConfig($config): void {
		$this->config = $config;
	}
}
