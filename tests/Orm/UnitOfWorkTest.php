<?php

#namespace Doctrine\Tests\ORM;

require_once 'lib/DoctrineTestInit.php';
require_once 'lib/mocks/Doctrine_EntityManagerMock.php';
require_once 'lib/mocks/Doctrine_ConnectionMock.php';
require_once 'lib/mocks/Doctrine_UnitOfWorkMock.php';
require_once 'lib/mocks/Doctrine_IdentityIdGeneratorMock.php';

/**
 * UnitOfWork tests.
 */
class Orm_UnitOfWorkTest extends Doctrine_OrmTestCase
{
    // SUT
    private $_unitOfWork;
    // Provides a sequence mock to the UnitOfWork
    private $_connectionMock;
    // The EntityManager mock that provides the mock persisters
    private $_emMock;
    
    protected function setUp() {
        parent::setUp();
        $this->_connectionMock = new Doctrine_ConnectionMock(array());
        $this->_emMock = Doctrine_EntityManagerMock::create($this->_connectionMock, "uowMockEm");
        // SUT
        $this->_unitOfWork = new Doctrine_UnitOfWorkMock($this->_emMock);
        $this->_emMock->setUnitOfWork($this->_unitOfWork);
    }
    
    protected function tearDown() {
    }
    
    public function testRegisterRemovedOnNewEntityIsIgnored()
    {
        $user = new ForumUser();
        $user->username = 'romanb';
        $this->assertFalse($this->_unitOfWork->isRegisteredRemoved($user));
        $this->_unitOfWork->registerDeleted($user);
        $this->assertFalse($this->_unitOfWork->isRegisteredRemoved($user));        
    }
    
    
    /* Operational tests */
    
    public function testSavingSingleEntityWithIdentityColumnForcesInsert()
    {
        // Setup fake persister and id generator for identity generation
        $userPersister = new Doctrine_EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata("ForumUser"));
        $this->_emMock->setEntityPersister('ForumUser', $userPersister);
        $idGeneratorMock = new Doctrine_IdentityIdGeneratorMock($this->_emMock);
        $this->_emMock->setIdGenerator('ForumUser', $idGeneratorMock);
        $userPersister->setMockIdGeneratorType(Doctrine_ORM_Mapping_ClassMetadata::GENERATOR_TYPE_IDENTITY);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $this->_unitOfWork->save($user);

        // Check
        $this->assertEquals(1, count($userPersister->getInserts())); // insert forced
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));   
        $this->assertTrue($this->_unitOfWork->isInIdentityMap($user));
        // should no longer be scheduled for insert
        $this->assertFalse($this->_unitOfWork->isRegisteredNew($user));        
        // should have an id
        $this->assertTrue(is_numeric($user->id));
        
        // Now lets check whether a subsequent commit() does anything
        $userPersister->reset();

        // Test
        $this->_unitOfWork->commit(); // shouldnt do anything
        
        // Check. Verify that nothing happened.
        $this->assertEquals(0, count($userPersister->getInserts()));
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));
    }

    /**
     * Tests a scenario where a save() operation is cascaded from a ForumUser
     * to its associated ForumAvatar, both entities using IDENTITY id generation.
     */
    public function testCascadedIdentityColumnInsert()
    {
        // Setup fake persister and id generator for identity generation
        //ForumUser
        $userPersister = new Doctrine_EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata("ForumUser"));
        $this->_emMock->setEntityPersister('ForumUser', $userPersister);  
        $userIdGeneratorMock = new Doctrine_IdentityIdGeneratorMock($this->_emMock);
        $this->_emMock->setIdGenerator('ForumUser', $userIdGeneratorMock);
        $userPersister->setMockIdGeneratorType(Doctrine_ORM_Mapping_ClassMetadata::GENERATOR_TYPE_IDENTITY);
        // ForumAvatar
        $avatarPersister = new Doctrine_EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata("ForumAvatar"));
        $this->_emMock->setEntityPersister('ForumAvatar', $avatarPersister);
        $avatarIdGeneratorMock = new Doctrine_IdentityIdGeneratorMock($this->_emMock);
        $this->_emMock->setIdGenerator('ForumAvatar', $avatarIdGeneratorMock);
        $avatarPersister->setMockIdGeneratorType(Doctrine_ORM_Mapping_ClassMetadata::GENERATOR_TYPE_IDENTITY);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $avatar = new ForumAvatar();
        $user->avatar = $avatar;
        $this->_unitOfWork->save($user); // save cascaded to avatar

        $this->assertTrue(is_numeric($user->id));
        $this->assertTrue(is_numeric($avatar->id));

        $this->assertEquals(1, count($userPersister->getInserts())); // insert forced
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));

        $this->assertEquals(1, count($avatarPersister->getInserts())); // insert forced
        $this->assertEquals(0, count($avatarPersister->getUpdates()));
        $this->assertEquals(0, count($avatarPersister->getDeletes()));
    }

    public function testComputeDataChangeSet()
    {
        $user1 = new ForumUser();
        $user1->id = 1;
        $user1->username = "romanb";
        $user1->avatar = new ForumAvatar();
        // Fake managed state
        $this->_unitOfWork->setEntityState($user1, Doctrine_ORM_UnitOfWork::STATE_MANAGED);

        $user2 = new ForumUser();
        $user2->id = 2;
        $user2->username = "jwage";
        // Fake managed state
        $this->_unitOfWork->setEntityState($user2, Doctrine_ORM_UnitOfWork::STATE_MANAGED);

        // Fake original entity date
        $this->_unitOfWork->setOriginalEntityData($user1, array(
            'id' => 1, 'username' => 'roman'
        ));
        $this->_unitOfWork->setOriginalEntityData($user2, array(
            'id' => 2, 'username' => 'jon'
        ));

        // Go
        $this->_unitOfWork->computeDataChangeSet(array($user1, $user2));

        // Verify
        $user1ChangeSet = $this->_unitOfWork->getDataChangeSet($user1);
        $this->assertTrue(is_array($user1ChangeSet));
        $this->assertEquals(2, count($user1ChangeSet));
        $this->assertTrue(isset($user1ChangeSet['username']));
        $this->assertEquals(array('roman' => 'romanb'), $user1ChangeSet['username']);
        $this->assertTrue(isset($user1ChangeSet['avatar']));
        $this->assertSame(array(null => $user1->avatar), $user1ChangeSet['avatar']);

        $user2ChangeSet = $this->_unitOfWork->getDataChangeSet($user2);
        $this->assertTrue(is_array($user2ChangeSet));
        $this->assertEquals(1, count($user2ChangeSet));
        $this->assertTrue(isset($user2ChangeSet['username']));
        $this->assertEquals(array('jon' => 'jwage'), $user2ChangeSet['username']);
    }

    /*
    public function testSavingSingleEntityWithSequenceIdGeneratorSchedulesInsert()
    {
        //...
    }
    
    public function testSavingSingleEntityWithTableIdGeneratorSchedulesInsert()
    {
        //...
    }
    
    public function testSavingSingleEntityWithSingleNaturalIdForcesInsert()
    {
        //...
    }
    
    public function testSavingSingleEntityWithCompositeIdForcesInsert()
    {
        //...
    }
    
    public function testSavingEntityGraphWithIdentityColumnsForcesInserts()
    {
        //...
    }
    
    public function testSavingEntityGraphWithSequencesDelaysInserts()
    {
        //...
    }
    
    public function testSavingEntityGraphWithNaturalIdsForcesInserts()
    {
        //...
    }
    
    public function testSavingEntityGraphWithMixedIdGenerationStrategies()
    {
        //...
    }
    */
}