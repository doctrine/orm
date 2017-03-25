<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
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
        $entityManager   = $this->getTestEntityManager();
        $metadataFactory = $entityManager->getMetadataFactory();
        $eventManager    = $entityManager->getEventManager();

        $eventManager->addEventListener(Events::loadClassMetadata, $this);

        $metadata = $metadataFactory->getMetadataFor(LoadEventTestEntity::class);
        $property = $metadata->getProperty('about');

        self::assertInstanceOf(Mapping\FieldMetadata::class, $property);

        $test = new LoadEventTestEntity();

        $property->setValue($test, 'About who?');

        self::assertEquals('About who?', $test->about);
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $metadata      = $eventArgs->getClassMetadata();
        $fieldMetadata = new Mapping\FieldMetadata('about');

        $fieldMetadata->setType(Type::getType('string'));
        $fieldMetadata->setLength(255);

        $metadata->setPropertyOverride($fieldMetadata);
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="load_event_test_entity")
 */
class LoadEventTestEntity
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    public $about;
}
