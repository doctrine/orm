<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataBuildingContext;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Reflection\ReflectionService;
use Doctrine\Tests\DoctrineTestCase;

/**
 * Tests for {@see \Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs}
 *
 * @covers \Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs
 */
class OnClassMetadataNotFoundEventArgsTest extends DoctrineTestCase
{
    public function testEventArgsMutability() : void
    {
        $entityManager           = $this->createMock(EntityManagerInterface::class);
        $metadataBuildingContext = new ClassMetadataBuildingContext(
            $this->createMock(ClassMetadataFactory::class),
            $this->createMock(ReflectionService::class)
        );

        $args = new OnClassMetadataNotFoundEventArgs('foo', $metadataBuildingContext, $entityManager);

        self::assertSame('foo', $args->getClassName());
        self::assertSame($metadataBuildingContext, $args->getClassMetadataBuildingContext());
        self::assertSame($entityManager, $args->getObjectManager());

        self::assertNull($args->getFoundMetadata());

        /** @var ClassMetadata $metadata */
        $metadata = $this->createMock(ClassMetadata::class);

        $args->setFoundMetadata($metadata);

        self::assertSame($metadata, $args->getFoundMetadata());

        $args->setFoundMetadata(null);

        self::assertNull($args->getFoundMetadata());
    }
}
