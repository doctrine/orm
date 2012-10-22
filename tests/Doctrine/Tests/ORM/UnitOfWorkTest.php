<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\UnitOfWork;
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
        $itemPersister = new EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata("Doctrine\Tests\ORM\NotifyChangedRelatedItem"));
        $this->_unitOfWork->setEntityPersister('Doctrine\Tests\ORM\NotifyChangedRelatedItem', $itemPersister);

        $entity = new NotifyChangedEntity;
        $entity->setData('thedata');
        $this->_unitOfWork->persist($entity);

        $this->_unitOfWork->commit();
        $this->assertEquals(1, count($persister->getInserts()));
        $persister->reset();

        $this->assertTrue($this->_unitOfWork->isInIdentityMap($entity));

        $entity->setData('newdata');
        $entity->setTransient('newtransientvalue');

        $this->assertTrue($this->_unitOfWork->isScheduledForDirtyCheck($entity));

        $this->assertEquals(array('data' => array('thedata', 'newdata')), $this->_unitOfWork->getEntityChangeSet($entity));

        $item = new NotifyChangedRelatedItem();
        $entity->getItems()->add($item);
        $item->setOwner($entity);
        $this->_unitOfWork->persist($item);

        $this->_unitOfWork->commit();
        $this->assertEquals(1, count($itemPersister->getInserts()));
        $persister->reset();
        $itemPersister->reset();


        $entity->getItems()->removeElement($item);
        $item->setOwner(null);
        $this->assertTrue($entity->getItems()->isDirty());
        $this->_unitOfWork->commit();
        $updates = $itemPersister->getUpdates();
        $this->assertEquals(1, count($updates));
        $this->assertTrue($updates[0] === $item);
    }

    public function testGetEntityStateOnVersionedEntityWithAssignedIdentifier()
    {
        $persister = new EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata("Doctrine\Tests\ORM\VersionedAssignedIdentifierEntity"));
        $this->_unitOfWork->setEntityPersister('Doctrine\Tests\ORM\VersionedAssignedIdentifierEntity', $persister);

        $e = new VersionedAssignedIdentifierEntity();
        $e->id = 42;
        $this->assertEquals(UnitOfWork::STATE_NEW, $this->_unitOfWork->getEntityState($e));
        $this->assertFalse($persister->isExistsCalled());
    }

    public function testGetEntityStateWithAssignedIdentity()
    {
        $persister = new EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata("Doctrine\Tests\Models\CMS\CmsPhonenumber"));
        $this->_unitOfWork->setEntityPersister('Doctrine\Tests\Models\CMS\CmsPhonenumber', $persister);

        $ph = new \Doctrine\Tests\Models\CMS\CmsPhonenumber();
        $ph->phonenumber = '12345';

        $this->assertEquals(UnitOfWork::STATE_NEW, $this->_unitOfWork->getEntityState($ph));
        $this->assertTrue($persister->isExistsCalled());

        $persister->reset();

        // if the entity is already managed the exists() check should be skipped
        $this->_unitOfWork->registerManaged($ph, array('phonenumber' => '12345'), array());
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->_unitOfWork->getEntityState($ph));
        $this->assertFalse($persister->isExistsCalled());
        $ph2 = new \Doctrine\Tests\Models\CMS\CmsPhonenumber();
        $ph2->phonenumber = '12345';
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $this->_unitOfWork->getEntityState($ph2));
        $this->assertFalse($persister->isExistsCalled());
    }

    /**
     * DDC-2086 [GH-484] Prevented "Undefined index" notice when updating.
     */
    public function testNoUndefinedIndexNoticeOnScheduleForUpdateWithoutChanges()
    {
        // Setup fake persister and id generator
        $userPersister = new EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata("Doctrine\Tests\Models\Forum\ForumUser"));
        $userPersister->setMockIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $this->_unitOfWork->setEntityPersister('Doctrine\Tests\Models\Forum\ForumUser', $userPersister);

        // Create a test user
        $user = new ForumUser();
        $user->name = 'Jasper';
        $this->_unitOfWork->persist($user);
        $this->_unitOfWork->commit();

        // Schedule user for update without changes
        $this->_unitOfWork->scheduleForUpdate($user);

        // This commit should not raise an E_NOTICE
        $this->_unitOfWork->commit();
    }
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
     * @GeneratedValue
     */
    private $id;
    /**
     * @Column(type="string")
     */
    private $data;

    private $transient; // not persisted

    /** @OneToMany(targetEntity="NotifyChangedRelatedItem", mappedBy="owner") */
    private $items;

    public function  __construct() {
        $this->items = new \Doctrine\Common\Collections\ArrayCollection;
    }

    public function getId() {
        return $this->id;
    }

    public function getItems() {
        return $this->items;
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

/** @Entity */
class NotifyChangedRelatedItem
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /** @ManyToOne(targetEntity="NotifyChangedEntity", inversedBy="items") */
    private $owner;

    public function getId() {
        return $this->id;
    }

    public function getOwner() {
        return $this->owner;
    }

    public function setOwner($owner) {
        $this->owner = $owner;
    }
}

/** @Entity */
class VersionedAssignedIdentifierEntity
{
    /**
     * @Id @Column(type="integer")
     */
    public $id;
    /**
     * @Version @Column(type="integer")
     */
    public $version;
}
