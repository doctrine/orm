<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

class LifecycleCallbackTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\LifecycleCallbackTestEntity'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\LifecycleCallbackTestUser')
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

        $reference->getId(); // trigger proxy load
        $this->assertTrue($reference->postLoadCallbackInvoked);
    }
}

/** @Entity @HasLifecycleCallbacks */
class LifecycleCallbackTestUser {
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
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
    
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @Column(type="string")
     */
    public $value;

    public function getId() {
        return $this->id;
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
}