<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\UnitOfWorkMock;
use Doctrine\Tests\Mocks\EntityPersisterMock;
use Doctrine\Tests\Mocks\IdentityIdGeneratorMock;
use Doctrine\Tests\Models\Forum\ForumUser;
use Doctrine\Tests\Models\Forum\ForumAvatar;

require_once __DIR__ . '/../TestInit.php';

/**
 * UnitOfWork tests.
 */
class UnitOfWorkTest extends \Doctrine\Tests\OrmTestCase
{
    // SUT
    private $_unitOfWork;
    // Provides a sequence mock to the UnitOfWork
    private $_connectionMock;
    // The EntityManager mock that provides the mock persisters
    private $_emMock;
    
    protected function setUp() {
        parent::setUp();
        $this->_connectionMock = new ConnectionMock(array(), new \Doctrine\Tests\Mocks\DriverMock());
        $this->_emMock = EntityManagerMock::create($this->_connectionMock);
        // SUT
        $this->_unitOfWork = new UnitOfWorkMock($this->_emMock);
        $this->_emMock->setUnitOfWork($this->_unitOfWork);
    }
    
    protected function tearDown() {
    }
    
    public function testRegisterRemovedOnNewEntityIsIgnored()
    {
        $user = new ForumUser();
        $user->username = 'romanb';
        $this->assertFalse($this->_unitOfWork->isScheduledForDelete($user));
        $this->_unitOfWork->scheduleForDelete($user);
        $this->assertFalse($this->_unitOfWork->isScheduledForDelete($user));        
    }
    
    
    /* Operational tests */
    
    public function testSavingSingleEntityWithIdentityColumnForcesInsert()
    {
        // Setup fake persister and id generator for identity generation
        $userPersister = new EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata("Doctrine\Tests\Models\Forum\ForumUser"));
        $this->_unitOfWork->setEntityPersister('Doctrine\Tests\Models\Forum\ForumUser', $userPersister);
        //$idGeneratorMock = new IdentityIdGeneratorMock($this->_emMock);
        //$this->_emMock->setIdGenerator('Doctrine\Tests\Models\Forum\ForumUser', $idGeneratorMock);
        $userPersister->setMockIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $this->_unitOfWork->persist($user);

        // Check
        $this->assertEquals(0, count($userPersister->getInserts()));
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));   
        $this->assertFalse($this->_unitOfWork->isInIdentityMap($user));
        // should no longer be scheduled for insert
        $this->assertTrue($this->_unitOfWork->isScheduledForInsert($user));
        
        // Now lets check whether a subsequent commit() does anything
        $userPersister->reset();

        // Test
        $this->_unitOfWork->commit();
        
        // Check.
        $this->assertEquals(1, count($userPersister->getInserts()));
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));
        
        // should have an id
        $this->assertTrue(is_numeric($user->id));
    }

    /**
     * Tests a scenario where a save() operation is cascaded from a ForumUser
     * to its associated ForumAvatar, both entities using IDENTITY id generation.
     */
    public function testCascadedIdentityColumnInsert()
    {
        // Setup fake persister and id generator for identity generation
        //ForumUser
        $userPersister = new EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata("Doctrine\Tests\Models\Forum\ForumUser"));
        $this->_unitOfWork->setEntityPersister('Doctrine\Tests\Models\Forum\ForumUser', $userPersister);
        //$userIdGeneratorMock = new IdentityIdGeneratorMock($this->_emMock);
        //$this->_emMock->setIdGenerator('Doctrine\Tests\Models\Forum\ForumUser', $userIdGeneratorMock);
        $userPersister->setMockIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY);
        // ForumAvatar
        $avatarPersister = new EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata("Doctrine\Tests\Models\Forum\ForumAvatar"));
        $this->_unitOfWork->setEntityPersister('Doctrine\Tests\Models\Forum\ForumAvatar', $avatarPersister);
        //$avatarIdGeneratorMock = new IdentityIdGeneratorMock($this->_emMock);
        //$this->_emMock->setIdGenerator('Doctrine\Tests\Models\Forum\ForumAvatar', $avatarIdGeneratorMock);
        $avatarPersister->setMockIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $avatar = new ForumAvatar();
        $user->avatar = $avatar;
        $this->_unitOfWork->persist($user); // save cascaded to avatar
        
        $this->_unitOfWork->commit();

        $this->assertTrue(is_numeric($user->id));
        $this->assertTrue(is_numeric($avatar->id));

        $this->assertEquals(1, count($userPersister->getInserts()));
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));

        $this->assertEquals(1, count($avatarPersister->getInserts()));
        $this->assertEquals(0, count($avatarPersister->getUpdates()));
        $this->assertEquals(0, count($avatarPersister->getDeletes()));
    }

    public function testChangeTrackingNotify()
    {
        $persister = new EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata("Doctrine\Tests\ORM\NotifyChangedEntity"));
        $this->_unitOfWork->setEntityPersister('Doctrine\Tests\ORM\NotifyChangedEntity', $persister);

        $entity = new NotifyChangedEntity;
        $entity->setData('thedata');
        $this->_unitOfWork->persist($entity);
        
        $this->_unitOfWork->commit();

        $this->assertTrue($this->_unitOfWork->isInIdentityMap($entity));

        $entity->setData('newdata');
        $entity->setTransient('newtransientvalue');

        $this->assertTrue($this->_unitOfWork->isScheduledForUpdate($entity));

        $this->assertEquals(array('data' => array('thedata', 'newdata')), $this->_unitOfWork->getEntityChangeSet($entity));
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

/**
 * @Entity
 */
class NotifyChangedEntity implements \Doctrine\Common\NotifyPropertyChanged
{
    private $_listeners = array();
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @Column(type="string")
     */
    private $data;

    private $transient; // not persisted

    public function getId() {
        return $this->id;
    }

    public function setTransient($value) {
        if ($value != $this->transient) {
            $this->_onPropertyChanged('transient', $this->transient, $value);
            $this->transient = $value;
        }
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        if ($data != $this->data) {
            $this->_onPropertyChanged('data', $this->data, $data);
            $this->data = $data;
        }
    }

    public function addPropertyChangedListener(\Doctrine\Common\PropertyChangedListener $listener)
    {
        $this->_listeners[] = $listener;
    }

    protected function _onPropertyChanged($propName, $oldValue, $newValue) {
        if ($this->_listeners) {
            foreach ($this->_listeners as $listener) {
                $listener->propertyChanged($this, $propName, $oldValue, $newValue);
            }
        }
    }
}