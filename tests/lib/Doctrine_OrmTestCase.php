<?php

require_once 'lib/mocks/Doctrine_DriverMock.php';
require_once 'lib/mocks/Doctrine_ConnectionMock.php';

/**
 * Base testcase class for all orm testcases.
 *
 */
class Doctrine_OrmTestCase extends Doctrine_TestCase
{
    protected $_em;

    protected function setUp() {
        if (isset($this->sharedFixture['em'])) {
            $this->_em = $this->sharedFixture['em'];
        } else {
            $config = new Doctrine_Common_Configuration();
            $eventManager = new Doctrine_Common_EventManager();
            $connectionOptions = array(
                'driverClass' => 'Doctrine_DriverMock',
                'wrapperClass' => 'Doctrine_ConnectionMock',
                'user' => 'john',
                'password' => 'wayne'      
            );
            $em = Doctrine_ORM_EntityManager::create($connectionOptions, 'mockEM', $config, $eventManager);
            $this->_em = $em;
        }
        $this->_em->activate();
    }
}
