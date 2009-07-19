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
        
        $this->assertTrue($entity->preSaveCallbackInvoked);
        $this->assertTrue($entity->postSaveCallbackInvoked);
        
        $this->_em->clear();
        
        $query = $this->_em->createQuery("select e from Doctrine\Tests\ORM\Functional\LifecycleCallbackTestEntity e");
        $result = $query->getResultList();
        $this->assertTrue($result[0]->postLoadCallbackInvoked);
        
        $result[0]->value = 'hello again';
        
        $this->_em->flush();
        
        $this->assertEquals('changed from preUpdate callback!', $result[0]->value);
    }
}

/**
 * @Entity
 * @LifecycleListener
 * @Table(name="lifecycle_callback_test_entity")
 */
class LifecycleCallbackTestEntity
{
    /* test stuff */
    public $preSaveCallbackInvoked = false;
    public $postSaveCallbackInvoked = false;
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
    
    /** @PreSave */
    public function doStuffOnPreSave() {
        $this->preSaveCallbackInvoked = true;
    }
    
    /** @PostSave */
    public function doStuffOnPostSave() {
        $this->postSaveCallbackInvoked = true;
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

