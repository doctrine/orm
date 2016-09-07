<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping;
use Doctrine\Tests\OrmTestCase;

class ClassMetadataLoadEventTest extends OrmTestCase
{
    /**
     * @group DDC-1610
     */
    public function testEvent()
    {
        $entityManager   = $this->_getTestEntityManager();
        $metadataFactory = $entityManager->getMetadataFactory();
        $eventManager    = $entityManager->getEventManager();

        $eventManager->addEventListener(Events::loadClassMetadata, $this);

        $metadata = $metadataFactory->getMetadataFor(LoadEventTestEntity::class);

        self::assertTrue($metadata->hasField('about'));
        self::assertArrayHasKey('about', $metadata->reflFields);
        self::assertInstanceOf('ReflectionProperty', $metadata->reflFields['about']);
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $metadata = $eventArgs->getClassMetadata();
        $fieldMetadata = new Mapping\FieldMetadata('about');

        $fieldMetadata->setType(Type::getType('string'));
        $fieldMetadata->setLength(255);

        $metadata->addProperty($fieldMetadata);
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
