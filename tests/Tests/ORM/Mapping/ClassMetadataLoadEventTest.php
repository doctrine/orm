<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\Group;
use ReflectionProperty;

class ClassMetadataLoadEventTest extends OrmTestCase
{
    #[Group('DDC-1610')]
    public function testEvent(): void
    {
        $em              = $this->getTestEntityManager();
        $metadataFactory = $em->getMetadataFactory();
        $evm             = $em->getEventManager();
        $evm->addEventListener(Events::loadClassMetadata, $this);
        $classMetadata = $metadataFactory->getMetadataFor(LoadEventTestEntity::class);
        self::assertTrue($classMetadata->hasField('about'));
        self::assertArrayHasKey('about', $classMetadata->reflFields);
        self::assertInstanceOf(ReflectionProperty::class, $classMetadata->reflFields['about']);
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $classMetadata = $eventArgs->getClassMetadata();
        $field         = [
            'fieldName' => 'about',
            'type' => 'string',
            'length' => 255,
        ];
        $classMetadata->mapField($field);
    }
}

#[Table(name: 'load_event_test_entity')]
#[Entity]
class LoadEventTestEntity
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[Column(type: 'string', length: 255)]
    private string $name;

    /** @var mixed */
    private $about;
}
