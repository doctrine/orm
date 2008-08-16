<?php
require_once 'lib/DoctrineTestInit.php';
require_once 'lib/mocks/Doctrine_EntityManagerMock.php';
require_once 'lib/mocks/Doctrine_ConnectionMock.php';

/**
 * EntityPersister tests.
 */
class Orm_EntityPersisterTest extends Doctrine_OrmTestCase
{
    private $_persister; // SUT
    private $_connMock;
    private $_emMock;
    
    protected function setUp() {
        parent::setUp();
        $this->_connMock = new Doctrine_ConnectionMock(array());
        $this->_emMock = new Doctrine_EntityManagerMock($this->_connMock);
        $this->_connMock->setDatabasePlatform(new Doctrine_DatabasePlatformMock());
        
        $this->_persister = new Doctrine_EntityPersister_Standard(
                $this->_emMock, $this->_emMock->getClassMetadata("ForumUser"));
    }
    
    public function testTest() {
        $user = new ForumUser();
        $user->username = "romanb";
        
        $user->avatar = new ForumAvatar();
        
        $this->_persister->insert($user);
        
        $inserts = $this->_connMock->getInserts();
        //var_dump($inserts);
        $this->assertTrue(isset($inserts['forum_user']));
        $this->assertEquals(1, count($inserts['forum_user']));
        $this->assertEquals(1, count($inserts['forum_user'][0]));
        $this->assertTrue(isset($inserts['forum_user'][0]['username']));
        $this->assertEquals('romanb', $inserts['forum_user'][0]['username']);
    }
    
}