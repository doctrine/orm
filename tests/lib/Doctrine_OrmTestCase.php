<?php

#namespace Doctrine\Tests;

require_once 'lib/mocks/Doctrine_DriverMock.php';
require_once 'lib/mocks/Doctrine_ConnectionMock.php';

/**
 * Base testcase class for all ORM testcases.
 */
class Doctrine_OrmTestCase extends Doctrine_TestCase
{
    /**
     * Creates an EntityManager for testing purposes.
     *
     * @return Doctrine\ORM\EntityManager
     */
    protected function _getTestEntityManager($conf = null, $eventManager = null) {
        $config = new Doctrine_ORM_Configuration();
        $eventManager = new Doctrine_Common_EventManager();
        $connectionOptions = array(
                'driverClass' => 'Doctrine_DriverMock',
                'wrapperClass' => 'Doctrine_ConnectionMock',
                'user' => 'john',
                'password' => 'wayne'
        );
        return Doctrine_ORM_EntityManager::create($connectionOptions, 'mockEM', $config, $eventManager);
    }
}
