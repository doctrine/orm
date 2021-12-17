<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

use function assert;

/**
 * Tests for {@see \Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs}
 *
 * @covers \Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs
 */
class OnClassMetadataNotFoundEventArgsTest extends TestCase
{
    public function testEventArgsMutability(): void
    {
        $objectManager = $this->createMock(ObjectManager::class);
        assert($objectManager instanceof ObjectManager);

        $args = new OnClassMetadataNotFoundEventArgs('foo', $objectManager);

        self::assertSame('foo', $args->getClassName());
        self::assertSame($objectManager, $args->getObjectManager());

        self::assertNull($args->getFoundMetadata());

        $metadata = $this->createMock(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata);

        $args->setFoundMetadata($metadata);

        self::assertSame($metadata, $args->getFoundMetadata());

        $args->setFoundMetadata(null);

        self::assertNull($args->getFoundMetadata());
    }
}
