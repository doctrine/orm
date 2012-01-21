<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Events;

require_once __DIR__ . '/../../TestInit.php';

class ClassMetadataLoadEventTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @group DDC-1610
     */
    public function testEvent()
    {
        $em = $this->_getTestEntityManager();
        $metadataFactory = $em->getMetadataFactory();
        $evm = $em->getEventManager();
        $evm->addEventListener(Events::loadClassMetadata, $this);
        $classMetadata = $metadataFactory->getMetadataFor('Doctrine\Tests\ORM\Mapping\LoadEventTestEntity');
        $this->assertTrue($classMetadata->hasField('about'));
        $this->assertArrayHasKey('about', $classMetadata->reflFields);
        $this->assertInstanceOf('ReflectionProperty', $classMetadata->reflFields['about']);
    }

    public function loadClassMetadata(\Doctrine\ORM\Event\LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();
        $field = array(
            'fieldName' => 'about',
            'type' => 'string',
            'length' => 255
        );
        $classMetadata->mapField($field);
    }
}

/**
 * @Entity
 * @Table(name="load_event_test_entity")
 */
class LoadEventTestEntity
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @Column(type="string", length=255)
     */
    private $name;

    private $about;
}
