<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Query;
use Doctrine\Tests\OrmFunctionalTestCase;

class LifecycleCallbackTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(LifecycleCallbackEventArgEntity::class),
                $this->_em->getClassMetadata(LifecycleCallbackTestEntity::class),
                $this->_em->getClassMetadata(LifecycleCallbackTestUser::class),
                $this->_em->getClassMetadata(LifecycleCallbackCascader::class),
                ]
            );
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testPreSavePostSaveCallbacksAreInvoked()
    {
        $entity = new LifecycleCallbackTestEntity;
        $entity->value = 'hello';
        $this->_em->persist($entity);
        $this->_em->flush();

        self::assertTrue($entity->prePersistCallbackInvoked);
        self::assertTrue($entity->postPersistCallbackInvoked);

        $this->_em->clear();

        $query = $this->_em->createQuery("select e from Doctrine\Tests\ORM\Functional\LifecycleCallbackTestEntity e");
        $result = $query->getResult();
        self::assertTrue($result[0]->postLoadCallbackInvoked);

        $result[0]->value = 'hello again';

        $this->_em->flush();

        self::assertEquals('changed from preUpdate callback!', $result[0]->value);
    }

    public function testPreFlushCallbacksAreInvoked()
    {
        $entity = new LifecycleCallbackTestEntity;
        $entity->value = 'hello';
        $this->_em->persist($entity);

        $this->_em->flush();

        self::assertTrue($entity->prePersistCallbackInvoked);
        self::assertTrue($entity->preFlushCallbackInvoked);

        $entity->preFlushCallbackInvoked = false;
        $this->_em->flush();

        self::assertTrue($entity->preFlushCallbackInvoked);

        $entity->value = 'bye';
        $entity->preFlushCallbackInvoked = false;
        $this->_em->flush();

        self::assertTrue($entity->preFlushCallbackInvoked);
    }

    public function testChangesDontGetLost()
    {
        $user = new LifecycleCallbackTestUser;
        $user->setName('Bob');
        $user->setValue('value');
        $this->_em->persist($user);
        $this->_em->flush();

        $user->setName('Alice');
        $this->_em->flush(); // Triggers preUpdate

        $this->_em->clear();

        $user2 = $this->_em->find(get_class($user), $user->getId());

        self::assertEquals('Alice', $user2->getName());
        self::assertEquals('Hello World', $user2->getValue());
    }

    /**
     * @group DDC-194
     */
    public function testGetReferenceWithPostLoadEventIsDelayedUntilProxyTrigger()
    {
        $entity = new LifecycleCallbackTestEntity;
        $entity->value = 'hello';
        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->getId();

        $this->_em->clear();

        $reference = $this->_em->getReference(LifecycleCallbackTestEntity::class, $id);
        self::assertFalse($reference->postLoadCallbackInvoked);

        $reference->getValue(); // trigger proxy load
        self::assertTrue($reference->postLoadCallbackInvoked);
    }

    /**
     * @group DDC-958
     */
    public function testPostLoadTriggeredOnRefresh()
    {
        $entity = new LifecycleCallbackTestEntity;
        $entity->value = 'hello';
        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->getId();

        $this->_em->clear();

        $reference = $this->_em->find(LifecycleCallbackTestEntity::class, $id);
        self::assertTrue($reference->postLoadCallbackInvoked);
        $reference->postLoadCallbackInvoked = false;

        $this->_em->refresh($reference);
        self::assertTrue($reference->postLoadCallbackInvoked, "postLoad should be invoked when refresh() is called.");
    }

    /**
     * @group DDC-113
     */
    public function testCascadedEntitiesCallsPrePersist()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $e1 = new LifecycleCallbackTestEntity;
        $e2 = new LifecycleCallbackTestEntity;

        $c = new LifecycleCallbackCascader();
        $this->_em->persist($c);

        $c->entities[] = $e1;
        $c->entities[] = $e2;
        $e1->cascader = $c;
        $e2->cascader = $c;

        //$this->_em->persist($c);
        $this->_em->flush();

        self::assertTrue($e1->prePersistCallbackInvoked);
        self::assertTrue($e2->prePersistCallbackInvoked);
    }

    /**
     * @group DDC-54
     * @group DDC-3005
     */
    public function testCascadedEntitiesLoadedInPostLoad()
    {
        $e1 = new LifecycleCallbackTestEntity();
        $e2 = new LifecycleCallbackTestEntity();

        $c = new LifecycleCallbackCascader();
        $this->_em->persist($c);

        $c->entities[] = $e1;
        $c->entities[] = $e2;
        $e1->cascader = $c;
        $e2->cascader = $c;

        $this->_em->flush();
        $this->_em->clear();

        $dql = <<<'DQL'
SELECT
    e, c
FROM
    Doctrine\Tests\ORM\Functional\LifecycleCallbackTestEntity AS e
LEFT JOIN
    e.cascader AS c
WHERE
    e.id IN (%s, %s)
DQL;

        $entities = $this
            ->_em
            ->createQuery(sprintf($dql, $e1->getId(), $e2->getId()))
            ->getResult();

        self::assertTrue(current($entities)->postLoadCallbackInvoked);
        self::assertTrue(current($entities)->postLoadCascaderNotNull);
        self::assertTrue(current($entities)->cascader->postLoadCallbackInvoked);
        self::assertEquals(current($entities)->cascader->postLoadEntitiesCount, 2);
    }

    /**
     * @group DDC-54
     * @group DDC-3005
     */
    public function testCascadedEntitiesNotLoadedInPostLoadDuringIteration()
    {
        $e1 = new LifecycleCallbackTestEntity();
        $e2 = new LifecycleCallbackTestEntity();

        $c = new LifecycleCallbackCascader();
        $this->_em->persist($c);

        $c->entities[] = $e1;
        $c->entities[] = $e2;
        $e1->cascader = $c;
        $e2->cascader = $c;

        $this->_em->flush();
        $this->_em->clear();

        $dql = <<<'DQL'
SELECT
    e, c
FROM
    Doctrine\Tests\ORM\Functional\LifecycleCallbackTestEntity AS e
LEFT JOIN
    e.cascader AS c
WHERE
    e.id IN (%s, %s)
DQL;

        $result = $this
            ->_em
            ->createQuery(sprintf($dql, $e1->getId(), $e2->getId()))
            ->iterate();

        foreach ($result as $entity) {
            self::assertTrue($entity[0]->postLoadCallbackInvoked);
            self::assertFalse($entity[0]->postLoadCascaderNotNull);

            break;
        }
    }
    /**
     * @group DDC-54
     * @group DDC-3005
     */
    public function testCascadedEntitiesNotLoadedInPostLoadDuringIterationWithSimpleObjectHydrator()
    {
        $this->_em->persist(new LifecycleCallbackTestEntity());
        $this->_em->persist(new LifecycleCallbackTestEntity());

        $this->_em->flush();
        $this->_em->clear();

        $result = $this
            ->_em
            ->createQuery('SELECT e FROM Doctrine\Tests\ORM\Functional\LifecycleCallbackTestEntity AS e')
            ->iterate(null, Query::HYDRATE_SIMPLEOBJECT);

        foreach ($result as $entity) {
            self::assertTrue($entity[0]->postLoadCallbackInvoked);
            self::assertFalse($entity[0]->postLoadCascaderNotNull);

            break;
        }
    }

    public function testLifecycleCallbacksGetInherited()
    {
        $childMeta = $this->_em->getClassMetadata(LifecycleCallbackChildEntity::class);
        self::assertEquals(['prePersist' => [0 => 'doStuff']], $childMeta->lifecycleCallbacks);
    }

    public function testLifecycleListener_ChangeUpdateChangeSet()
    {
        $listener = new LifecycleListenerPreUpdate;
        $this->_em->getEventManager()->addEventListener(['preUpdate'], $listener);

        $user = new LifecycleCallbackTestUser;
        $user->setName('Bob');
        $user->setValue('value');
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT u FROM Doctrine\Tests\ORM\Functional\LifecycleCallbackTestUser u WHERE u.name = 'Bob'";
        $bob = $this->_em->createQuery($dql)->getSingleResult();
        $bob->setName('Alice');

        $this->_em->flush(); // preUpdate reverts Alice to Bob
        $this->_em->clear();

        $this->_em->getEventManager()->removeEventListener(['preUpdate'], $listener);

        $bob = $this->_em->createQuery($dql)->getSingleResult();

        self::assertEquals('Bob', $bob->getName());
    }

    /**
    * @group DDC-1955
    */
    public function testLifecycleCallbackEventArgs()
    {
        $e = new LifecycleCallbackEventArgEntity;

        $e->value = 'foo';
        $this->_em->persist($e);
        $this->_em->flush();

        $e->value = 'var';
        $this->_em->persist($e);
        $this->_em->flush();

        $this->_em->refresh($e);

        $this->_em->remove($e);
        $this->_em->flush();


        self::assertArrayHasKey('preFlushHandler', $e->calls);
        self::assertArrayHasKey('postLoadHandler', $e->calls);
        self::assertArrayHasKey('prePersistHandler', $e->calls);
        self::assertArrayHasKey('postPersistHandler', $e->calls);
        self::assertArrayHasKey('preUpdateHandler', $e->calls);
        self::assertArrayHasKey('postUpdateHandler', $e->calls);
        self::assertArrayHasKey('preRemoveHandler', $e->calls);
        self::assertArrayHasKey('postRemoveHandler', $e->calls);

        self::assertInstanceOf(PreFlushEventArgs::class, $e->calls['preFlushHandler']);
        self::assertInstanceOf(LifecycleEventArgs::class, $e->calls['postLoadHandler']);
        self::assertInstanceOf(LifecycleEventArgs::class, $e->calls['prePersistHandler']);
        self::assertInstanceOf(LifecycleEventArgs::class, $e->calls['postPersistHandler']);
        self::assertInstanceOf(PreUpdateEventArgs::class, $e->calls['preUpdateHandler']);
        self::assertInstanceOf(LifecycleEventArgs::class, $e->calls['postUpdateHandler']);
        self::assertInstanceOf(LifecycleEventArgs::class, $e->calls['preRemoveHandler']);
        self::assertInstanceOf(LifecycleEventArgs::class, $e->calls['postRemoveHandler']);
    }
}

/** @Entity @HasLifecycleCallbacks */
class LifecycleCallbackTestUser {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string") */
    private $value;
    /** @Column(type="string") */
    private $name;
    public function getId() {return $this->id;}
    public function getValue() {return $this->value;}
    public function setValue($value) {$this->value = $value;}
    public function getName() {return $this->name;}
    public function setName($name) {$this->name = $name;}
    /** @PreUpdate */
    public function testCallback() {$this->value = 'Hello World';}
}

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="lc_cb_test_entity")
 */
class LifecycleCallbackTestEntity
{
    /* test stuff */
    public $prePersistCallbackInvoked = false;
    public $postPersistCallbackInvoked = false;
    public $postLoadCallbackInvoked = false;
    public $postLoadCascaderNotNull = false;
    public $preFlushCallbackInvoked = false;

    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @Column(type="string", nullable=true)
     */
    public $value;

    /**
     * @ManyToOne(targetEntity="LifecycleCallbackCascader")
     * @JoinColumn(name="cascader_id", referencedColumnName="id")
     */
    public $cascader;

    public function getId() {
        return $this->id;
    }

    public function getValue() {
        return $this->value;
    }

    /** @PrePersist */
    public function doStuffOnPrePersist() {
        $this->prePersistCallbackInvoked = true;
    }

    /** @PostPersist */
    public function doStuffOnPostPersist() {
        $this->postPersistCallbackInvoked = true;
    }

    /** @PostLoad */
    public function doStuffOnPostLoad() {
        $this->postLoadCallbackInvoked = true;
        $this->postLoadCascaderNotNull = isset($this->cascader);
    }

    /** @PreUpdate */
    public function doStuffOnPreUpdate() {
        $this->value = 'changed from preUpdate callback!';
    }

    /** @PreFlush */
    public function doStuffOnPreFlush() {
        $this->preFlushCallbackInvoked = true;
    }
}

/**
 * @Entity @HasLifecycleCallbacks
 * @Table(name="lc_cb_test_cascade")
 */
class LifecycleCallbackCascader
{
    /* test stuff */
    public $postLoadCallbackInvoked = false;
    public $postLoadEntitiesCount = 0;

    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @OneToMany(targetEntity="LifecycleCallbackTestEntity", mappedBy="cascader", cascade={"persist"})
     */
    public $entities;

    public function __construct()
    {
        $this->entities = new ArrayCollection();
    }

    /** @PostLoad */
    public function doStuffOnPostLoad() {
        $this->postLoadCallbackInvoked = true;
        $this->postLoadEntitiesCount = count($this->entities);
    }
}

/** @MappedSuperclass @HasLifecycleCallbacks */
class LifecycleCallbackParentEntity {
    /** @PrePersist */
    function doStuff() {

    }
}

/** @Entity @Table(name="lc_cb_childentity") */
class LifecycleCallbackChildEntity extends LifecycleCallbackParentEntity {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
}

class LifecycleListenerPreUpdate
{
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $eventArgs->setNewValue('name', 'Bob');
    }
}

/** @Entity @HasLifecycleCallbacks */
class LifecycleCallbackEventArgEntity
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column() */
    public $value;

    public $calls = [];

    /**
     * @PostPersist
     */
    public function postPersistHandler(LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PrePersist
     */
    public function prePersistHandler(LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PostUpdate
     */
    public function postUpdateHandler(LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PreUpdate
     */
    public function preUpdateHandler(PreUpdateEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PostRemove
     */
    public function postRemoveHandler(LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PreRemove
     */
    public function preRemoveHandler(LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PreFlush
     */
    public function preFlushHandler(PreFlushEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PostLoad
     */
    public function postLoadHandler(LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }
}
