<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_EntityManagerTest extends Doctrine_OrmTestCase
{
    
    protected function setUp() {
        parent::setUp();
    }
    
    protected function tearDown() {
        Doctrine_EntityManager::unbindAllManagers();
        Doctrine_EntityManager::releaseAllManagers();
        parent::tearDown();
    }
    
    public function testInstantiationRegistersInstanceInStaticMap()
    {
        $em = new Doctrine_EntityManager(new Doctrine_Connection_Mock());
        $this->assertSame($em, Doctrine_EntityManager::getManager('SomeEntity'));
    }
    
    public function testStaticGetManagerThrowsExceptionIfNoManagerAvailable()
    {
        try {
            Doctrine_EntityManager::getManager('SomeEntity');
            $this->fail("Expected exception not thrown.");
        } catch (Doctrine_EntityManager_Exception $ex) {}
    }
    
    public function testBindingValidEntityToNamedManager()
    {
        $em = new Doctrine_EntityManager(new Doctrine_Connection_Mock(null), 'myEM');
        Doctrine_EntityManager::bindEntityToManager('SomeEntity', 'myEM');
        $this->assertSame($em, Doctrine_EntityManager::getManager('SomeEntity'));
    }
    
    public function testBindingEntityToInvalidManagerThrowsExceptionOnRetrieval()
    {
        // will work. we don't check the existence of the EM during binding
        Doctrine_EntityManager::bindEntityToManager('SomeEntity', 'myEM');
        // exception on access
        try {
            Doctrine_EntityManager::getManager('SomeEntity');
            $this->fail();
        } catch (Doctrine_EntityManager_Exception $ex) {}
    }
    
}