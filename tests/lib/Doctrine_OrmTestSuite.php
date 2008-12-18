<?php 
/**
 * The outermost test suite for all orm related testcases & suites.
 *
 * Currently the orm suite uses a normal connection object.
 * Upon separation of the DBAL and ORM package this suite should just use a orm
 * connection/session/manager instance as the shared fixture.
 */
class Doctrine_OrmTestSuite extends Doctrine_TestSuite
{
    protected function setUp()
    {
        $config = new Doctrine_ORM_Configuration();
        $eventManager = new Doctrine_Common_EventManager();
        $connectionOptions = array(
            'driverClass' => 'Doctrine_DriverMock',
            'wrapperClass' => 'Doctrine_ConnectionMock',
            'user' => 'john',
            'password' => 'wayne'      
        );      
        $em = Doctrine_ORM_EntityManager::create($connectionOptions, 'mockEM', $config, $eventManager);
        $this->sharedFixture['em'] = $em;
    }
    
    protected function tearDown()
    {} 
}