<?php

namespace Doctrine\Tests;

/**
 * Base testcase class for all ORM testcases.
 */
class OrmTestCase extends DoctrineTestCase
{
    /** The metadata cache that is shared between all ORM tests (except functional tests). */
    private static $_metadataCacheImpl = null;

    /**
     * Creates an EntityManager for testing purposes.
     *
     * @return Doctrine\ORM\EntityManager
     */
    protected function _getTestEntityManager($conf = null, $eventManager = null)
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(self::getSharedMetadataCacheImpl());
        $eventManager = new \Doctrine\Common\EventManager();
        $connectionOptions = array(
                'driverClass' => 'Doctrine\Tests\Mocks\DriverMock',
                'wrapperClass' => 'Doctrine\Tests\Mocks\ConnectionMock',
                'user' => 'john',
                'password' => 'wayne'
        );
        return \Doctrine\ORM\EntityManager::create($connectionOptions, $config, $eventManager);
    }

    private static function getSharedMetadataCacheImpl()
    {
        if (is_null(self::$_metadataCacheImpl)) {
            self::$_metadataCacheImpl = new \Doctrine\ORM\Cache\ArrayCache;
        }
        return self::$_metadataCacheImpl;
    }
}
