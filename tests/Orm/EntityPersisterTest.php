<?php
require_once 'lib/DoctrineTestInit.php';
require_once 'lib/mocks/Doctrine_EntityManagerMock.php';
require_once 'lib/mocks/Doctrine_ConnectionMock.php';
require_once 'lib/mocks/Doctrine_ClassMetadataMock.php';

/**
 * EntityPersister tests.
 */
class Orm_EntityPersisterTest extends Doctrine_OrmTestCase
{
    private $_persister; // SUT
    private $_connMock;
    private $_emMock;
    private $_idGenMock;
    private $classMetadataMock;
    
    protected function setUp() {
        parent::setUp();
        $this->_connMock = new Doctrine_ConnectionMock(array());
        $this->_emMock = Doctrine_EntityManagerMock::create($this->_connMock, 'persisterMockEM');
        $this->_idGenMock = new Doctrine_SequenceMock($this->_emMock);
        $this->_classMetadataMock = new Doctrine_ClassMetadataMock("ForumUser", $this->_emMock);
        $this->_classMetadataMock->setIdGenerator($this->_idGenMock);
        $this->_connMock->setDatabasePlatform(new Doctrine_DatabasePlatformMock());        
        $this->_persister = new Doctrine_EntityPersister_Standard(
                $this->_emMock, $this->_emMock->getClassMetadata("ForumUser"));
                
        $this->_emMock->activate();
    }
    
    public function testInsert() {
        $user = new ForumUser();
        $user->username = "romanb";
        $user->avatar = new ForumAvatar();
        
        //insert
        $this->_persister->insert($user->avatar);
        $inserts = $this->_connMock->getInserts();
        //check
        $this->assertEquals(1, count($inserts));
        $this->assertEquals(null, $user->avatar->id);
        $user->avatar->id = 0; // fake we got id
        $this->assertTrue(isset($inserts['forum_avatar']));
        $this->assertEquals(1, count($inserts['forum_avatar']));
        $this->assertTrue(empty($inserts['forum_avatar'][0]));
        
        //insert
        $this->_persister->insert($user);
        $inserts = $this->_connMock->getInserts();
        //check
        $this->assertEquals(2, count($inserts));
        $this->assertEquals(null, $user->id);
        $this->assertTrue(isset($inserts['forum_user']));
        $this->assertEquals(1, count($inserts['forum_user']));
        $this->assertEquals(3, count($inserts['forum_user'][0]));
        //username column
        $this->assertTrue(isset($inserts['forum_user'][0]['username']));
        $this->assertEquals('romanb', $inserts['forum_user'][0]['username']);
        //avatar_id join column
        $this->assertTrue(isset($inserts['forum_user'][0]['avatar_id']));
        $this->assertEquals(0, $inserts['forum_user'][0]['avatar_id']);
        //dtype discriminator column
        $this->assertTrue(isset($inserts['forum_user'][0]['dtype']));
        $this->assertEquals('user', $inserts['forum_user'][0]['dtype']);
    }
    
}