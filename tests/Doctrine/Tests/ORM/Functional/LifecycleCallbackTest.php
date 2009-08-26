<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

class LifecycleCallbackTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\LifecycleCallbackTestEntity')
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
}

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="lifecycle_callback_test_entity")
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
     * @Column(type="string", length=255)
     */
    public $value;
    
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