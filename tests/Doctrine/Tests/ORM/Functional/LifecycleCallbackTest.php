<?php

namespace Doctrine\Tests\ORM\Functional;
use Doctrine\ORM\Event\PreUpdateEventArgs;

require_once __DIR__ . '/../../TestInit.php';

class LifecycleCallbackTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\LifecycleCallbackEventArgEntity'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\LifecycleCallbackTestEntity'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\LifecycleCallbackTestUser'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\LifecycleCallbackCascader'),
            ));
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

        $this->assertTrue($entity->prePersistCallbackInvoked);
        $this->assertTrue($entity->postPersistCallbackInvoked);

        $this->_em->clear();

        $query = $this->_em->createQuery("select e from Doctrine\Tests\ORM\Functional\LifecycleCallbackTestEntity e");
        $result = $query->getResult();
        $this->assertTrue($result[0]->postLoadCallbackInvoked);

        $result[0]->value = 'hello again';

        $this->_em->flush();

        $this->assertEquals('changed from preUpdate callback!', $result[0]->value);
    }

    public function testPreFlushCallbacksAreInvoked()
    {
        $entity = new LifecycleCallbackTestEntity;
        $entity->value = 'hello';
        $this->_em->persist($entity);

        $this->_em->flush();

        $this->assertTrue($entity->prePersistCallbackInvoked);
        $this->assertTrue($entity->preFlushCallbackInvoked);

        $entity->preFlushCallbackInvoked = false;
        $this->_em->flush();

        $this->assertTrue($entity->preFlushCallbackInvoked);

        $entity->value = 'bye';
        $entity->preFlushCallbackInvoked = false;
        $this->_em->flush();

        $this->assertTrue($entity->preFlushCallbackInvoked);
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

        $this->assertEquals('Alice', $user2->getName());
        $this->assertEquals('Hello World', $user2->getValue());
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

        $reference = $this->_em->getReference('Doctrine\Tests\ORM\Functional\LifecycleCallbackTestEntity', $id);
        $this->assertFalse($reference->postLoadCallbackInvoked);

        $reference->getValue(); // trigger proxy load
        $this->assertTrue($reference->postLoadCallbackInvoked);
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

        $reference = $this->_em->find('Doctrine\Tests\ORM\Functional\LifecycleCallbackTestEntity', $id);
        $this->assertTrue($reference->postLoadCallbackInvoked);
        $reference->postLoadCallbackInvoked = false;

        $this->_em->refresh($reference);
        $this->assertTrue($reference->postLoadCallbackInvoked, "postLoad should be invoked when refresh() is called.");
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

        $this->assertTrue($e1->prePersistCallbackInvoked);
        $this->assertTrue($e2->prePersistCallbackInvoked);
    }

    public function testLifecycleCallbacksGetInherited()
    {
        $childMeta = $this->_em->getClassMetadata(__NAMESPACE__ . '\LifecycleCallbackChildEntity');
        $this->assertEquals(array('prePersist' => array(0 => 'doStuff')), $childMeta->lifecycleCallbacks);
    }

    public function testLifecycleListener_ChangeUpdateChangeSet()
    {
        $listener = new LifecycleListenerPreUpdate;
        $this->_em->getEventManager()->addEventListener(array('preUpdate'), $listener);

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

        $this->_em->getEventManager()->removeEventListener(array('preUpdate'), $listener);

        $bob = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertEquals('Bob', $bob->getName());
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


        $this->assertArrayHasKey('preFlushHandler', $e->calls);
        $this->assertArrayHasKey('postLoadHandler', $e->calls);
        $this->assertArrayHasKey('prePersistHandler', $e->calls);
        $this->assertArrayHasKey('postPersistHandler', $e->calls);
        $this->assertArrayHasKey('preUpdateHandler', $e->calls);
        $this->assertArrayHasKey('postUpdateHandler', $e->calls);
        $this->assertArrayHasKey('preRemoveHandler', $e->calls);
        $this->assertArrayHasKey('postRemoveHandler', $e->calls);

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\PreFlushEventArgs',
            $e->calls['preFlushHandler']
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $e->calls['postLoadHandler']
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $e->calls['prePersistHandler']
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $e->calls['postPersistHandler']
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\PreUpdateEventArgs',
            $e->calls['preUpdateHandler']
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $e->calls['postUpdateHandler']
        );
 
        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $e->calls['preRemoveHandler']
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $e->calls['postRemoveHandler']
        );
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
 * @Entity
 * @Table(name="lc_cb_test_cascade")
 */
class LifecycleCallbackCascader
{
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
        $this->entities = new \Doctrine\Common\Collections\ArrayCollection();
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

    public $calls = array();

    /**
     * @PostPersist
     */
    public function postPersistHandler(\Doctrine\ORM\Event\LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PrePersist
     */
    public function prePersistHandler(\Doctrine\ORM\Event\LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PostUpdate
     */
    public function postUpdateHandler(\Doctrine\ORM\Event\LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PreUpdate
     */
    public function preUpdateHandler(\Doctrine\ORM\Event\PreUpdateEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PostRemove
     */
    public function postRemoveHandler(\Doctrine\ORM\Event\LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PreRemove
     */
    public function preRemoveHandler(\Doctrine\ORM\Event\LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PreFlush
     */
    public function preFlushHandler(\Doctrine\ORM\Event\PreFlushEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }

    /**
     * @PostLoad
     */
    public function postLoadHandler(\Doctrine\ORM\Event\LifecycleEventArgs $event)
    {
        $this->calls[__FUNCTION__] = $event;
    }
}