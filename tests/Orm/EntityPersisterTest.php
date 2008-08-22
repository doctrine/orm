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
    private $_seqManagerMock;
    
    protected function setUp() {
        parent::setUp();
        $this->_connMock = new Doctrine_ConnectionMock(array());
        $this->_emMock = new Doctrine_EntityManagerMock($this->_connMock);
        $this->_seqManagerMock = new Doctrine_SequenceMock($this->_connMock);
        
        $this->_connMock->setDatabasePlatform(new Doctrine_DatabasePlatformMock());
        $this->_connMock->setSequenceManager($this->_seqManagerMock);
        
        $this->_persister = new Doctrine_EntityPersister_Standard(
                $this->_emMock, $this->_emMock->getClassMetadata("ForumUser"));
    }
    
    public function testInsert() {
        $user = new ForumUser();
        $user->username = "romanb";
        $user->avatar = new ForumAvatar();
        
        $this->_seqManagerMock->autoinc(); //fake identity column autoinc
        $this->_persister->insert($user->avatar);
        $inserts = $this->_connMock->getInserts();
        //check
        $this->assertEquals(1, count($inserts));
        $this->assertEquals(0, $user->avatar->id);
        $this->assertTrue(isset($inserts['forum_avatar']));
        $this->assertEquals(1, count($inserts['forum_avatar']));
        $this->assertTrue(empty($inserts['forum_avatar'][0]));
        
        $this->_seqManagerMock->autoinc(); //fake identity column autoinc
        $this->_persister->insert($user);
        $inserts = $this->_connMock->getInserts();
        //check
        $this->assertEquals(2, count($inserts));
        $this->assertEquals(1, $user->id);
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