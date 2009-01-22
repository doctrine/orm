<?php

namespace Doctrine\Tests;

/**
 * Base testcase class for all ORM testcases.
 */
class OrmTestCase extends DoctrineTestCase
{
    /**
     * Creates an EntityManager for testing purposes.
     *
     * @return Doctrine\ORM\EntityManager
     */
    protected function _getTestEntityManager($conf = null, $eventManager = null) {
        $config = new \Doctrine\ORM\Configuration();
        $eventManager = new \Doctrine\Common\EventManager();
        $connectionOptions = array(
                'driverClass' => 'Doctrine\Tests\Mocks\DriverMock',
                'wrapperClass' => 'Doctrine\Tests\Mocks\ConnectionMock',
                'user' => 'john',
                'password' => 'wayne'
        );
        return \Doctrine\ORM\EntityManager::create($connectionOptions, 'mockEM', $config, $eventManager);
    }
}
