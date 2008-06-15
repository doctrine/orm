<?php
/**
 * Base testcase class for all orm testcases.
 *
 */
class Doctrine_OrmTestCase extends Doctrine_TestCase
{
    protected $_em;
    protected $_emf;
    
    protected function setUp() {
        if (isset($this->sharedFixture['emf'], $this->sharedFixture['em'])) {
            $this->_emf = $this->sharedFixture['emf'];
            $this->_em = $this->sharedFixture['em'];
        } else {
            $emf = new Doctrine_EntityManagerFactory();
            $emf->setConfiguration(new Doctrine_Configuration());
            $emf->setEventManager(new Doctrine_EventManager());
            $connectionOptions = array(
                'driver' => 'mock',
                'user' => 'john',
                'password' => 'wayne'      
            );      
            $em = $emf->createEntityManager($connectionOptions, 'mockEM');
            $this->_emf = $emf;
            $this->_em = $em;
        }
    }
}
