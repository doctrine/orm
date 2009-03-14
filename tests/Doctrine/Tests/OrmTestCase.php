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
     * NOTE: The created EntityManager will have its dependant DBAL parts completely
     * mocked out using a DriverMock, ConnectionMock, etc. These mocks can then
     * be configured in the tests to simulate the DBAL behavior that is desired
     * for a particular test,
     *
     * @return Doctrine\ORM\EntityManager
     */
    protected function _getTestEntityManager($conn = null, $conf = null, $eventManager = null)
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(self::getSharedMetadataCacheImpl());
        $eventManager = new \Doctrine\Common\EventManager();
        if ($conn === null) {
            $conn = array(
                'driverClass' => 'Doctrine\Tests\Mocks\DriverMock',
                'wrapperClass' => 'Doctrine\Tests\Mocks\ConnectionMock',
                'user' => 'john',
                'password' => 'wayne'
            );
        }
        return \Doctrine\ORM\EntityManager::create($conn, $config, $eventManager);
    }

    private static function getSharedMetadataCacheImpl()
    {
        if (self::$_metadataCacheImpl === null) {
            self::$_metadataCacheImpl = new \Doctrine\ORM\Cache\ArrayCache;
        }
        return self::$_metadataCacheImpl;
    }
}
