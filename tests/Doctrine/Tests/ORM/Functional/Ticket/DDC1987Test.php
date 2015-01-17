<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Proxy\ProxyFactory;

/**
 * @group DDC-1987
 */
class DDC1987Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected $_em;

    public function setUp()
    {
        parent::setUp();

        $this->_em = $this->_getEntityManager();
        $this->_em->setUnitOfWork(new UnitOfWorkMock($this->_em));

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata('\Doctrine\Tests\Models\DDC1987\DDC1987Order'),
            $this->_em->getClassMetadata('\Doctrine\Tests\Models\DDC1987\DDC1987Item'),
        ));

        $order = new \Doctrine\Tests\Models\DDC1987\DDC1987Order(1);
        $item = new \Doctrine\Tests\Models\DDC1987\DDC1987Item($order, 1);
        $order->getItems()->add($item);

        $this->_em->persist($order);
        $this->_em->flush();
    }

    public function testCascadeRefresh()
    {
        $order = $this->_em->find('Doctrine\Tests\Models\DDC1987\DDC1987Order', 1);
        $item = $order->getItems()->first();

        // Assume some other process changes $item->price and persists this change to the database.
        // To be sure we're working with the latest version, we call $this->_em->refresh($order),
        // which *should* cascade to $item.

        $this->_em->refresh($order);

        $this->assertEquals($this->_em->getUnitOfWork()->visited, array(
            'Doctrine\Tests\Models\DDC1987\DDC1987Order',
            'Doctrine\Tests\Models\DDC1987\DDC1987Item'
        ));
    }

    /**
     * Creates an EntityManagerMock. Code mostly copied from OrmFunctionalTestCase.
     */
    protected function _getEntityManager($config = null, $eventManager = null)
    {
        // NOTE: Functional tests use their own shared metadata cache, because
        // the actual database platform used during execution has effect on some
        // metadata mapping behaviors (like the choice of the ID generation).
        if (is_null(self::$_metadataCacheImpl)) {
            if (isset($GLOBALS['DOCTRINE_CACHE_IMPL'])) {
                self::$_metadataCacheImpl = new $GLOBALS['DOCTRINE_CACHE_IMPL'];
            } else {
                self::$_metadataCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
            }
        }

        if (is_null(self::$_queryCacheImpl)) {
            self::$_queryCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
        }

        $this->_sqlLoggerStack = new \Doctrine\DBAL\Logging\DebugStack();
        $this->_sqlLoggerStack->enabled = false;

        //FIXME: two different configs! $conn and the created entity manager have
        // different configs.
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(self::$_metadataCacheImpl);
        $config->setQueryCacheImpl(self::$_queryCacheImpl);
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');

        $enableSecondLevelCache = getenv('ENABLE_SECOND_LEVEL_CACHE');

        if ($this->isSecondLevelCacheEnabled || $enableSecondLevelCache) {

            $cacheConfig    = new \Doctrine\ORM\Cache\CacheConfiguration();
            $cache          = $this->getSharedSecondLevelCacheDriverImpl();
            $factory        = new DefaultCacheFactory($cacheConfig->getRegionsConfiguration(), $cache);

            $this->secondLevelCacheFactory = $factory;

            if ($this->isSecondLevelCacheLogEnabled) {
                $this->secondLevelCacheLogger = new StatisticsCacheLogger();
                $cacheConfig->setCacheLogger($this->secondLevelCacheLogger);
            }

            $cacheConfig->setCacheFactory($factory);
            $config->setSecondLevelCacheEnabled(true);
            $config->setSecondLevelCacheConfiguration($cacheConfig);

            $this->isSecondLevelCacheEnabled = true;
        }

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(
            realpath(__DIR__ . '/Models/Cache')
        ), true));

        $conn = static::$_sharedConn;
        $conn->getConfiguration()->setSQLLogger($this->_sqlLoggerStack);

        // get rid of more global state
        $evm = $conn->getEventManager();
        foreach ($evm->getListeners() AS $event => $listeners) {
            foreach ($listeners AS $listener) {
                $evm->removeEventListener(array($event), $listener);
            }
        }

        if ($enableSecondLevelCache) {
            $evm->addEventListener('loadClassMetadata', new CacheMetadataListener());
        }

        if (isset($GLOBALS['db_event_subscribers'])) {
            foreach (explode(",", $GLOBALS['db_event_subscribers']) AS $subscriberClass) {
                $subscriberInstance = new $subscriberClass();
                $evm->addEventSubscriber($subscriberInstance);
            }
        }

        if (isset($GLOBALS['debug_uow_listener'])) {
            $evm->addEventListener(array('onFlush'), new \Doctrine\ORM\Tools\DebugUnitOfWorkListener());
        }

        return EntityManagerMock::create($conn, $config);
    }

    protected function tearDown()
    {
    }
}

class UnitOfWorkMock extends \Doctrine\ORM\UnitOfWork
{
    public $visited;

    protected function doRefresh($entity, array &$visited)
    {
        $this->visited[] = get_class($entity);

        parent::doRefresh($entity, $visited);
    }
}

class EntityManagerMock extends \Doctrine\ORM\EntityManager
{
    protected function __construct(Connection $conn, Configuration $config, EventManager $eventManager)
    {
        $this->conn              = $conn;
        $this->config            = $config;
        $this->eventManager      = $eventManager;

        $metadataFactoryClassName = $config->getClassMetadataFactoryName();

        $this->metadataFactory = new $metadataFactoryClassName;
        $this->metadataFactory->setEntityManager($this);
        $this->metadataFactory->setCacheDriver($this->config->getMetadataCacheImpl());

        $this->repositoryFactory = $config->getRepositoryFactory();
        $this->unitOfWork        = new UnitOfWorkMock($this);
        $this->proxyFactory      = new ProxyFactory(
            $this,
            $config->getProxyDir(),
            $config->getProxyNamespace(),
            $config->getAutoGenerateProxyClasses()
        );

        if ($config->isSecondLevelCacheEnabled()) {
            $cacheConfig    = $config->getSecondLevelCacheConfiguration();
            $cacheFactory   = $cacheConfig->getCacheFactory();
            $this->cache    = $cacheFactory->createCache($this);
        }
    }

    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
        if ( ! $config->getMetadataDriverImpl()) {
            throw ORMException::missingMappingDriverImpl();
        }

        switch (true) {
            case (is_array($conn)):
                $conn = \Doctrine\DBAL\DriverManager::getConnection(
                    $conn, $config, ($eventManager ?: new EventManager())
                );
                break;

            case ($conn instanceof Connection):
                if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
                     throw ORMException::mismatchedEventManager();
                }
                break;

            default:
                throw new \InvalidArgumentException("Invalid argument: " . $conn);
        }

        return new EntityManagerMock($conn, $config, $conn->getEventManager());
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function setUnitOfWork(UnitOfWorkMock $unitOfWork)
    {
        $this->unitOfWork = $unitOfWork;
    }

    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }
}
