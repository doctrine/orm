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
        if (isset($this->sharedFixture['em'])) {
            $this->_em = $this->sharedFixture['em'];
        } else { 
            $config = new Doctrine_Configuration();
            $eventManager = new Doctrine_EventManager();
            $connectionOptions = array(
                'driver' => 'Doctrine_ConnectionMock',
                'user' => 'john',
                'password' => 'wayne'      
            );
            $em = Doctrine_EntityManager::create($connectionOptions, 'mockEM', $config, $eventManager);
            $this->_em = $em;
        }
        $this->_em->activate();
    }
}
