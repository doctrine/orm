<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_UnitOfWorkTestCase extends Doctrine_OrmTestCase
{
    private $_unitOfWork;
    private $_user;
    
    protected function setUp() {
        $this->_user = new ForumUser();
        $this->_unitOfWork = $this->sharedFixture['connection']->unitOfWork;
    }
    
    protected function tearDown() {
        $this->_user->free();
    }
    
    public function testTransientEntityIsManaged()
    {
        $this->assertTrue($this->_unitOfWork->isManaged($this->_user));
        $this->assertSame($this->_user, $this->_unitOfWork->get($this->_user->getOid()));
    }
    
    public function testDetachSingleEntity()
    {
        $this->assertTrue($this->_unitOfWork->detach($this->_user));
        try {
            $this->_unitOfWork->get($this->_user->getOid());
            $this->fail("Entity is still managed after is has been detached.");
        } catch (Doctrine_Connection_Exception $ex) {}
    }
    
    public function testDetachAllEntities()
    {
        $this->assertEquals(1, $this->_unitOfWork->detachAll());
        try {
            $this->_unitOfWork->get($this->_user->getOid());
            $this->fail("Entity is still managed after all entities have been detached.");
        } catch (Doctrine_Connection_Exception $ex) {}
    }
    
    /*public function testSavedEntityHasIdentityAndIsManaged()
    {
        $this->_user->username = 'romanb';
        $this->_user->save();
        $this->assertTrue($this->_unitOfWork->hasIdentity($this->_user));
        $this->assertTrue($this->_unitOfWork->isManaged($this->_user));
    }*/
}