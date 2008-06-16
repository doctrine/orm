<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_UnitOfWorkTest extends Doctrine_OrmTestCase
{
    private $_unitOfWork;
    private $_user;
    
    protected function setUp() {
        parent::setUp();
        $this->_user = new ForumUser();
        $this->_unitOfWork = $this->_em->getUnitOfWork();
    }
    
    protected function tearDown() {
        $this->_user->free();
    }
    
    public function testRegisterNew()
    {
        $this->_user->username = 'romanb';
        $this->_user->id = 1;
        // registerNew() is normally called in save()/persist()
        $this->_unitOfWork->registerNew($this->_user);
        $this->assertTrue($this->_unitOfWork->isRegisteredNew($this->_user));
        $this->assertTrue($this->_unitOfWork->contains($this->_user));
        $this->assertFalse($this->_unitOfWork->isRegisteredDirty($this->_user));
        $this->assertFalse($this->_unitOfWork->isRegisteredRemoved($this->_user));
    }
    
    public function testRegisterDirty()
    {
        $this->_user->username = 'romanb';
        $this->_user->id = 1;
        $this->assertEquals(Doctrine_Entity::STATE_TDIRTY, $this->_user->_state());
        $this->assertFalse($this->_unitOfWork->contains($this->_user));
        $this->_unitOfWork->registerDirty($this->_user);
        $this->assertTrue($this->_unitOfWork->isRegisteredDirty($this->_user));
        $this->assertFalse($this->_unitOfWork->isRegisteredNew($this->_user));
        $this->assertFalse($this->_unitOfWork->isRegisteredRemoved($this->_user));
    }
    
    public function testRegisterRemovedOnTransientEntityIsIgnored()
    {
        $this->_user->username = 'romanb';
        $this->_user->id = 1;
        $this->assertFalse($this->_unitOfWork->isRegisteredRemoved($this->_user));
        $this->_unitOfWork->registerRemoved($this->_user);
        $this->assertFalse($this->_unitOfWork->isRegisteredRemoved($this->_user));        
    }
    
    /*public function testSavedEntityHasIdentityAndIsManaged()
    {
        $this->_user->username = 'romanb';
        $this->_user->save();
        $this->assertTrue($this->_unitOfWork->hasIdentity($this->_user));
        $this->assertTrue($this->_unitOfWork->isManaged($this->_user));
    }*/
}