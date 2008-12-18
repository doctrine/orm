<?php
require_once 'lib/DoctrineTestInit.php';
require_once 'lib/mocks/Doctrine_EntityManagerMock.php';
require_once 'lib/mocks/Doctrine_ConnectionMock.php';
require_once 'lib/mocks/Doctrine_ClassMetadataMock.php';
require_once 'lib/mocks/Doctrine_UnitOfWorkMock.php';

/**
 * EntityPersister tests.
 */
class Orm_EntityPersisterTest extends Doctrine_OrmTestCase
{
    private $_connMock;
    private $_emMock;
    private $_idGenMock;
    private $_uowMock;
    
    protected function setUp() {
        parent::setUp();
        $this->_connMock = new Doctrine_ConnectionMock(array());
        $this->_emMock = Doctrine_EntityManagerMock::create($this->_connMock, 'persisterMockEM');
        $this->_uowMock = new Doctrine_UnitOfWorkMock($this->_emMock);
        $this->_emMock->setUnitOfWork($this->_uowMock);
        $this->_idGenMock = new Doctrine_SequenceMock($this->_emMock);
        $this->_emMock->setIdGenerator('ForumUser', $this->_idGenMock);
                
        $this->_emMock->activate();
    }
    
    public function testSimpleInsert() {
        $userPersister = new Doctrine_ORM_Persisters_StandardEntityPersister(
                $this->_emMock, $this->_emMock->getClassMetadata("ForumUser"));
        $avatarPersister = new Doctrine_ORM_Persisters_StandardEntityPersister(
                $this->_emMock, $this->_emMock->getClassMetadata("ForumAvatar"));

        $user = new ForumUser();
        $user->username = "romanb";
        $user->avatar = new ForumAvatar();

        $this->_uowMock->setDataChangeSet($user, array(
                'username' => array('' => 'romanb'),
                'avatar' => array('' => $user->avatar)));


        //insert
        $avatarPersister->insert($user->avatar);
        $inserts = $this->_connMock->getInserts();
        //check
        $this->assertEquals(1, count($inserts));
        $this->assertTrue(isset($inserts['forum_avatar']));
        $this->assertEquals(1, count($inserts['forum_avatar']));
        $this->assertEquals(null, $user->avatar->id);
        $user->avatar->id = 0; // Fake that we got an id

        //insert
        $userPersister->insert($user);
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