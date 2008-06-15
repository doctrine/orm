<?php
require_once 'lib/DoctrineTestInit.php';

#namespace Doctrine::Tests::ORM;

/**
 * EntityManagerFactory tests.
 */
class Orm_EntityManagerFactoryTest extends Doctrine_OrmTestCase
{
    private $_mockOptions = array('driver' => 'mock', 'user' => '', 'password' => '');
    
    protected function tearDown() {
        parent::tearDown();
    }
    
    private function _createNamedManager($name)
    {
        return $this->_emf->createEntityManager($this->_mockOptions, $name);
    }
    
    public function testBindingEntityToNamedManager()
    {
        $myEM = $this->_createNamedManager('myEM');
        $this->_emf->bindEntityToManager('SomeEntity', 'myEM');
        $this->assertSame($myEM, $this->_emf->getEntityManager('SomeEntity'));
        $this->_emf->releaseEntityManager('myEM');
    }

    public function testStaticLookup()
    {
        $this->assertTrue(Doctrine_EntityManagerFactory::getManager() instanceof Doctrine_EntityManager);
    }
    
}