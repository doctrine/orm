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
        $emf = new Doctrine_EntityManagerFactory();
        $emf->setConfiguration(new Doctrine_Configuration());
        $emf->setEventManager(new Doctrine_EventManager());
        $connectionOptions = array(
            'driver' => 'mock',
            'user' => 'john',
            'password' => 'wayne'      
        );      
        $em = $emf->createEntityManager($connectionOptions, 'mockEM');
        $this->sharedFixture['emf'] = $emf;
        $this->sharedFixture['em'] = $em;
    }
    
    protected function tearDown()
    {} 
}