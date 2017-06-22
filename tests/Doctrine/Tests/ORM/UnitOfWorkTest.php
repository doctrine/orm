<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventManager;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\GeneratorType;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\EntityPersisterMock;
use Doctrine\Tests\Mocks\UnitOfWorkMock;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Forum\ForumAvatar;
use Doctrine\Tests\Models\Forum\ForumUser;
use Doctrine\Tests\Models\GeoNames\City;
use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\OrmTestCase;
use stdClass;

/**
 * UnitOfWork tests.
 */
class UnitOfWorkTest extends OrmTestCase
{
    /**
     * SUT
     *
     * @var UnitOfWorkMock
     */
    private $unitOfWork;

    /**
     * Provides a sequence mock to the UnitOfWork
     *
     * @var ConnectionMock
     */
    private $connectionMock;

    /**
     * The EntityManager mock that provides the mock persisters
     *
     * @var EntityManagerMock
     */
    private $emMock;

    /**
     * @var EventManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventManager;

    protected function setUp()
    {
        parent::setUp();

        $this->connectionMock = new ConnectionMock([], new DriverMock());
        $this->eventManager   = $this->getMockBuilder(EventManager::class)->getMock();
        $this->emMock         = EntityManagerMock::create($this->connectionMock, null, $this->eventManager);
        $this->unitOfWork     = new UnitOfWorkMock($this->emMock);

        $this->emMock->setUnitOfWork($this->unitOfWork);
    }

    public function testRegisterRemovedOnNewEntityIsIgnored()
    {
        $user = new ForumUser();
        $user->username = 'romanb';
        self::assertFalse($this->unitOfWork->isScheduledForDelete($user));
        $this->unitOfWork->scheduleForDelete($user);
        self::assertFalse($this->unitOfWork->isScheduledForDelete($user));
    }


    /* Operational tests */

    public function testSavingSingleEntityWithIdentityColumnForcesInsert()
    {
        // Setup fake persister and id generator for identity generation
        $userPersister = new EntityPersisterMock($this->emMock, $this->emMock->getClassMetadata(ForumUser::class));
        $this->unitOfWork->setEntityPersister(ForumUser::class, $userPersister);
        $userPersister->setMockIdGeneratorType(GeneratorType::IDENTITY);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $this->unitOfWork->persist($user);

        // Check
        self::assertEquals(0, count($userPersister->getInserts()));
        self::assertEquals(0, count($userPersister->getUpdates()));
        self::assertEquals(0, count($userPersister->getDeletes()));
        self::assertFalse($this->unitOfWork->isInIdentityMap($user));
        // should no longer be scheduled for insert
        self::assertTrue($this->unitOfWork->isScheduledForInsert($user));

        // Now lets check whether a subsequent commit() does anything
        $userPersister->reset();

        // Test
        $this->unitOfWork->commit();

        // Check.
        self::assertEquals(1, count($userPersister->getInserts()));
        self::assertEquals(0, count($userPersister->getUpdates()));
        self::assertEquals(0, count($userPersister->getDeletes()));

        // should have an id
        self::assertTrue(is_numeric($user->id));
    }

    /**
     * Tests a scenario where a save() operation is cascaded from a ForumUser
     * to its associated ForumAvatar, both entities using IDENTITY id generation.
     */
    public function testCascadedIdentityColumnInsert()
    {
        // Setup fake persister and id generator for identity generation
        //ForumUser
        $userPersister = new EntityPersisterMock($this->emMock, $this->emMock->getClassMetadata(ForumUser::class));
        $this->unitOfWork->setEntityPersister(ForumUser::class, $userPersister);
        $userPersister->setMockIdGeneratorType(GeneratorType::IDENTITY);
        // ForumAvatar
        $avatarPersister = new EntityPersisterMock($this->emMock, $this->emMock->getClassMetadata(ForumAvatar::class));
        $this->unitOfWork->setEntityPersister(ForumAvatar::class, $avatarPersister);
        $avatarPersister->setMockIdGeneratorType(GeneratorType::IDENTITY);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $avatar = new ForumAvatar();
        $user->avatar = $avatar;
        $this->unitOfWork->persist($user); // save cascaded to avatar

        $this->unitOfWork->commit();

        self::assertTrue(is_numeric($user->id));
        self::assertTrue(is_numeric($avatar->id));

        self::assertEquals(1, count($userPersister->getInserts()));
        self::assertEquals(0, count($userPersister->getUpdates()));
        self::assertEquals(0, count($userPersister->getDeletes()));

        self::assertEquals(1, count($avatarPersister->getInserts()));
        self::assertEquals(0, count($avatarPersister->getUpdates()));
        self::assertEquals(0, count($avatarPersister->getDeletes()));
    }

    public function testChangeTrackingNotify()
    {
        $persister = new EntityPersisterMock($this->emMock, $this->emMock->getClassMetadata(NotifyChangedEntity::class));
        $this->unitOfWork->setEntityPersister(NotifyChangedEntity::class, $persister);
        $itemPersister = new EntityPersisterMock($this->emMock, $this->emMock->getClassMetadata(NotifyChangedRelatedItem::class));
        $this->unitOfWork->setEntityPersister(NotifyChangedRelatedItem::class, $itemPersister);

        $entity = new NotifyChangedEntity;
        $entity->setData('thedata');
        $this->unitOfWork->persist($entity);

        $this->unitOfWork->commit();
        self::assertEquals(1, count($persister->getInserts()));
        $persister->reset();

        self::assertTrue($this->unitOfWork->isInIdentityMap($entity));

        $entity->setData('newdata');
        $entity->setTransient('newtransientvalue');

        self::assertTrue($this->unitOfWork->isScheduledForDirtyCheck($entity));

        self::assertEquals(
            [
                'data' => ['thedata', 'newdata'],
                'transient' => [null, 'newtransientvalue'],
            ],
            $this->unitOfWork->getEntityChangeSet($entity)
        );

        $item = new NotifyChangedRelatedItem();
        $entity->getItems()->add($item);
        $item->setOwner($entity);
        $this->unitOfWork->persist($item);

        $this->unitOfWork->commit();
        self::assertEquals(1, count($itemPersister->getInserts()));
        $persister->reset();
        $itemPersister->reset();


        $entity->getItems()->removeElement($item);
        $item->setOwner(null);
        self::assertTrue($entity->getItems()->isDirty());
        $this->unitOfWork->commit();
        $updates = $itemPersister->getUpdates();
        self::assertEquals(1, count($updates));
        self::assertTrue($updates[0] === $item);
    }

    public function testGetEntityStateOnVersionedEntityWithAssignedIdentifier()
    {
        $persister = new EntityPersisterMock($this->emMock, $this->emMock->getClassMetadata(VersionedAssignedIdentifierEntity::class));
        $this->unitOfWork->setEntityPersister(VersionedAssignedIdentifierEntity::class, $persister);

        $e = new VersionedAssignedIdentifierEntity();
        $e->id = 42;
        self::assertEquals(UnitOfWork::STATE_NEW, $this->unitOfWork->getEntityState($e));
        self::assertFalse($persister->isExistsCalled());
    }

    public function testGetEntityStateWithAssignedIdentity()
    {
        $persister = new EntityPersisterMock($this->emMock, $this->emMock->getClassMetadata(CmsPhonenumber::class));
        $this->unitOfWork->setEntityPersister(CmsPhonenumber::class, $persister);

        $ph = new CmsPhonenumber();
        $ph->phonenumber = '12345';

        self::assertEquals(UnitOfWork::STATE_NEW, $this->unitOfWork->getEntityState($ph));
        self::assertTrue($persister->isExistsCalled());

        $persister->reset();

        // if the entity is already managed the exists() check should be skipped
        $this->unitOfWork->registerManaged($ph, ['phonenumber' => '12345'], []);
        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->unitOfWork->getEntityState($ph));
        self::assertFalse($persister->isExistsCalled());
        $ph2 = new CmsPhonenumber();
        $ph2->phonenumber = '12345';
        self::assertEquals(UnitOfWork::STATE_DETACHED, $this->unitOfWork->getEntityState($ph2));
        self::assertFalse($persister->isExistsCalled());
    }

    /**
     * DDC-2086 [GH-484] Prevented 'Undefined index' notice when updating.
     */
    public function testNoUndefinedIndexNoticeOnScheduleForUpdateWithoutChanges()
    {
        // Setup fake persister and id generator
        $userPersister = new EntityPersisterMock($this->emMock, $this->emMock->getClassMetadata(ForumUser::class));
        $userPersister->setMockIdGeneratorType(GeneratorType::IDENTITY);
        $this->unitOfWork->setEntityPersister(ForumUser::class, $userPersister);

        // Create a test user
        $user = new ForumUser();
        $user->name = 'Jasper';
        $this->unitOfWork->persist($user);
        $this->unitOfWork->commit();

        // Schedule user for update without changes
        $this->unitOfWork->scheduleForUpdate($user);

        self::assertNotEmpty($this->unitOfWork->getScheduledEntityUpdates());

        // This commit should not raise an E_NOTICE
        $this->unitOfWork->commit();

        self::assertEmpty($this->unitOfWork->getScheduledEntityUpdates());
    }

    /**
     * @group DDC-1984
     */
    public function testLockWithoutEntityThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->unitOfWork->lock(null, null, null);
    }

    /**
     * @group DDC-3490
     *
     * @dataProvider invalidAssociationValuesDataProvider
     *
     * @param mixed $invalidValue
     */
    public function testRejectsPersistenceOfObjectsWithInvalidAssociationValue($invalidValue)
    {
        $this->unitOfWork->setEntityPersister(
            ForumUser::class,
            new EntityPersisterMock(
                $this->emMock,
                $this->emMock->getClassMetadata(ForumUser::class)
            )
        );

        $user           = new ForumUser();
        $user->username = 'John';
        $user->avatar   = $invalidValue;

        $this->expectException(\Doctrine\ORM\ORMInvalidArgumentException::class);

        $this->unitOfWork->persist($user);
    }

    /**
     * @group DDC-3490
     *
     * @dataProvider invalidAssociationValuesDataProvider
     *
     * @param mixed $invalidValue
     */
    public function testRejectsChangeSetComputationForObjectsWithInvalidAssociationValue($invalidValue)
    {
        $metadata = $this->emMock->getClassMetadata(ForumUser::class);

        $this->unitOfWork->setEntityPersister(
            ForumUser::class,
            new EntityPersisterMock($this->emMock, $metadata)
        );

        $user = new ForumUser();

        $this->unitOfWork->persist($user);

        $user->username = 'John';
        $user->avatar   = $invalidValue;

        $this->expectException(\Doctrine\ORM\ORMInvalidArgumentException::class);

        $this->unitOfWork->computeChangeSet($metadata, $user);
    }

    /**
     * @group DDC-3619
     * @group 1338
     */
    public function testRemovedAndRePersistedEntitiesAreInTheIdentityMapAndAreNotGarbageCollected()
    {
        $entity     = new ForumUser();
        $entity->id = 123;

        $this->unitOfWork->registerManaged($entity, ['id' => 123], []);
        self::assertTrue($this->unitOfWork->isInIdentityMap($entity));

        $this->unitOfWork->remove($entity);
        self::assertFalse($this->unitOfWork->isInIdentityMap($entity));

        $this->unitOfWork->persist($entity);
        self::assertTrue($this->unitOfWork->isInIdentityMap($entity));
    }

    /**
     * @group 5849
     * @group 5850
     */
    public function testPersistedEntityAndClearManager()
    {
        $entity1 = new City(123, 'London');
        $entity2 = new Country(456, 'United Kingdom');

        $this->unitOfWork->persist($entity1);
        self::assertTrue($this->unitOfWork->isInIdentityMap($entity1));

        $this->unitOfWork->persist($entity2);
        self::assertTrue($this->unitOfWork->isInIdentityMap($entity2));

        $this->unitOfWork->clear();

        self::assertFalse($this->unitOfWork->isInIdentityMap($entity1));
        self::assertFalse($this->unitOfWork->isInIdentityMap($entity2));

        self::assertFalse($this->unitOfWork->isScheduledForInsert($entity1));
        self::assertFalse($this->unitOfWork->isScheduledForInsert($entity2));
    }

    /**
     * Data Provider
     *
     * @return mixed[][]
     */
    public function invalidAssociationValuesDataProvider()
    {
        return [
            ['foo'],
            [['foo']],
            [''],
            [[]],
            [new stdClass()],
            [new ArrayCollection()],
        ];
    }

    /**
     * @dataProvider entitiesWithValidIdentifiersProvider
     *
     * @param object $entity
     * @param string $idHash
     *
     * @return void
     */
    public function testAddToIdentityMapValidIdentifiers($entity, $idHash)
    {
        $this->unitOfWork->persist($entity);
        $this->unitOfWork->addToIdentityMap($entity);

        self::assertSame($entity, $this->unitOfWork->getByIdHash($idHash, get_class($entity)));
    }

    public function entitiesWithValidIdentifiersProvider()
    {
        $emptyString = new EntityWithStringIdentifier();

        $emptyString->id = '';

        $nonEmptyString = new EntityWithStringIdentifier();

        $nonEmptyString->id = uniqid('id', true);

        $emptyStrings = new EntityWithCompositeStringIdentifier();

        $emptyStrings->id1 = '';
        $emptyStrings->id2 = '';

        $nonEmptyStrings = new EntityWithCompositeStringIdentifier();

        $nonEmptyStrings->id1 = uniqid('id1', true);
        $nonEmptyStrings->id2 = uniqid('id2', true);

        $booleanTrue = new EntityWithBooleanIdentifier();

        $booleanTrue->id = true;

        $booleanFalse = new EntityWithBooleanIdentifier();

        $booleanFalse->id = false;

        return [
            'empty string, single field'     => [$emptyString, ''],
            'non-empty string, single field' => [$nonEmptyString, $nonEmptyString->id],
            'empty strings, two fields'      => [$emptyStrings, ' '],
            'non-empty strings, two fields'  => [$nonEmptyStrings, $nonEmptyStrings->id1 . ' ' . $nonEmptyStrings->id2],
            'boolean true'                   => [$booleanTrue, '1'],
            'boolean false'                  => [$booleanFalse, ''],
        ];
    }

    public function testRegisteringAManagedInstanceRequiresANonEmptyIdentifier()
    {
        $this->expectException(ORMInvalidArgumentException::class);

        $this->unitOfWork->registerManaged(new EntityWithBooleanIdentifier(), [], []);
    }

    /**
     * @dataProvider entitiesWithInvalidIdentifiersProvider
     *
     * @param object $entity
     * @param array  $identifier
     *
     * @return void
     */
    public function testAddToIdentityMapInvalidIdentifiers($entity, array $identifier)
    {
        $this->expectException(ORMInvalidArgumentException::class);

        $this->unitOfWork->registerManaged($entity, $identifier, []);
    }


    public function entitiesWithInvalidIdentifiersProvider()
    {
        $firstNullString  = new EntityWithCompositeStringIdentifier();

        $firstNullString->id2 = uniqid('id2', true);

        $secondNullString = new EntityWithCompositeStringIdentifier();

        $secondNullString->id1 = uniqid('id1', true);

        return [
            'null string, single field'      => [new EntityWithStringIdentifier(), ['id' => null]],
            'null strings, two fields'       => [new EntityWithCompositeStringIdentifier(), ['id1' => null, 'id2' => null]],
            'first null string, two fields'  => [$firstNullString, ['id1' => null, 'id2' => $firstNullString->id2]],
            'second null string, two fields' => [$secondNullString, ['id1' => $secondNullString->id1, 'id2' => null]],
        ];
    }

    /**
     * @group 5689
     * @group 1465
     */
    public function testObjectHashesOfMergedEntitiesAreNotUsedInOriginalEntityDataMap()
    {
        $user       = new CmsUser();
        $user->name = 'ocramius';
        $mergedUser = $this->unitOfWork->merge($user);

        self::assertSame([], $this->unitOfWork->getOriginalEntityData($user), 'No original data was stored');
        self::assertSame([], $this->unitOfWork->getOriginalEntityData($mergedUser), 'No original data was stored');


        $user       = null;
        $mergedUser = null;

        // force garbage collection of $user (frees the used object hashes, which may be recycled)
        gc_collect_cycles();

        $newUser       = new CmsUser();
        $newUser->name = 'ocramius';

        $this->unitOfWork->persist($newUser);

        self::assertSame([], $this->unitOfWork->getOriginalEntityData($newUser), 'No original data was stored');
    }

    /**
     * @group DDC-1955
     * @group 5570
     * @group 6174
     */
    public function testMergeWithNewEntityWillPersistItAndTriggerPrePersistListenersWithMergedEntityData()
    {
        $entity = new EntityWithRandomlyGeneratedField();

        $generatedFieldValue = $entity->generatedField;

        $this
            ->eventManager
            ->expects(self::any())
            ->method('hasListeners')
            ->willReturnCallback(function ($eventName) {
                return $eventName === Events::prePersist;
            });
        $this
            ->eventManager
            ->expects(self::once())
            ->method('dispatchEvent')
            ->with(
                self::anything(),
                self::callback(function (LifecycleEventArgs $args) use ($entity, $generatedFieldValue) {
                    /* @var $object EntityWithRandomlyGeneratedField */
                    $object = $args->getObject();

                    self::assertInstanceOf(EntityWithRandomlyGeneratedField::class, $object);
                    self::assertNotSame($entity, $object);
                    self::assertSame($generatedFieldValue, $object->generatedField);

                    return true;
                })
            );

        /* @var $object EntityWithRandomlyGeneratedField */
        $object = $this->unitOfWork->merge($entity);

        self::assertNotSame($object, $entity);
        self::assertInstanceOf(EntityWithRandomlyGeneratedField::class, $object);
        self::assertSame($object->generatedField, $entity->generatedField);
    }

    /**
     * @group DDC-1955
     * @group 5570
     * @group 6174
     */
    public function testMergeWithExistingEntityWillNotPersistItNorTriggerPrePersistListeners()
    {
        $persistedEntity = new EntityWithRandomlyGeneratedField();
        $mergedEntity    = new EntityWithRandomlyGeneratedField();

        $mergedEntity->id = $persistedEntity->id;
        $mergedEntity->generatedField = random_int(
            $persistedEntity->generatedField + 1,
            $persistedEntity->generatedField + 1000
        );

        $this
            ->eventManager
            ->expects(self::any())
            ->method('hasListeners')
            ->willReturnCallback(function ($eventName) {
                return $eventName === Events::prePersist;
            });
        $this->eventManager->expects(self::never())->method('dispatchEvent');

        $this->unitOfWork->registerManaged(
            $persistedEntity,
            ['id' => $persistedEntity->id],
            ['generatedField' => $persistedEntity->generatedField]
        );

        /* @var $merged EntityWithRandomlyGeneratedField */
        $merged = $this->unitOfWork->merge($mergedEntity);

        self::assertSame($merged, $persistedEntity);
        self::assertSame($persistedEntity->generatedField, $mergedEntity->generatedField);
    }
}

/**
 * @ORM\Entity
 */
class NotifyChangedEntity implements NotifyPropertyChanged
{
    private $listeners = [];
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;
    /**
     * @ORM\Column(type="string")
     */
    private $data;

    private $transient; // not persisted

    /** @ORM\OneToMany(targetEntity="NotifyChangedRelatedItem", mappedBy="owner") */
    private $items;

    public function  __construct() {
        $this->items = new ArrayCollection;
    }

    public function getId() {
        return $this->id;
    }

    public function getItems() {
        return $this->items;
    }

    public function setTransient($value) {
        if ($value != $this->transient) {
            $this->onPropertyChanged('transient', $this->transient, $value);
            $this->transient = $value;
        }
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        if ($data != $this->data) {
            $this->onPropertyChanged('data', $this->data, $data);
            $this->data = $data;
        }
    }

    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->listeners[] = $listener;
    }

    protected function onPropertyChanged($propName, $oldValue, $newValue) {
        if ($this->listeners) {
            foreach ($this->listeners as $listener) {
                $listener->propertyChanged($this, $propName, $oldValue, $newValue);
            }
        }
    }
}

/** @ORM\Entity */
class NotifyChangedRelatedItem
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /** @ORM\ManyToOne(targetEntity="NotifyChangedEntity", inversedBy="items") */
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

/** @ORM\Entity */
class VersionedAssignedIdentifierEntity
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     */
    public $id;
    /**
     * @ORM\Version @ORM\Column(type="integer")
     */
    public $version;
}

/** @ORM\Entity */
class EntityWithStringIdentifier
{
    /**
     * @ORM\Id @ORM\Column(type="string")
     *
     * @var string|null
     */
    public $id;
}

/** @ORM\Entity */
class EntityWithBooleanIdentifier
{
    /**
     * @ORM\Id @ORM\Column(type="boolean")
     *
     * @var bool|null
     */
    public $id;
}

/** @ORM\Entity */
class EntityWithCompositeStringIdentifier
{
    /**
     * @ORM\Id @ORM\Column(type="string")
     *
     * @var string|null
     */
    public $id1;

    /**
     * @ORM\Id @ORM\Column(type="string")
     *
     * @var string|null
     */
    public $id2;
}

/** @ORM\Entity */
class EntityWithRandomlyGeneratedField
{
    /** @ORM\Id @ORM\Column(type="string") */
    public $id;

    /**
     * @ORM\Column(type="integer")
     */
    public $generatedField;

    public function __construct()
    {
        $this->id             = uniqid('id', true);
        $this->generatedField = mt_rand(0, 100000);
    }
}
