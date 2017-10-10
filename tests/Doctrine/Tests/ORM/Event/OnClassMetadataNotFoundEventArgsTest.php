<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see \Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs}
 *
 * @covers \Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs
 */
class OnClassMetadataNotFoundEventArgsTest extends TestCase
{
    public function testEventArgsMutability()
    {
        /* @var $objectManager \Doctrine\Common\Persistence\ObjectManager */
        $objectManager = $this->createMock(ObjectManager::class);

        $args = new OnClassMetadataNotFoundEventArgs('foo', $objectManager);

        self::assertSame('foo', $args->getClassName());
        self::assertSame($objectManager, $args->getObjectManager());

        self::assertNull($args->getFoundMetadata());

        /* @var $metadata \Doctrine\Common\Persistence\Mapping\ClassMetadata */
        $metadata = $this->createMock(ClassMetadata::class);

        $args->setFoundMetadata($metadata);

        self::assertSame($metadata, $args->getFoundMetadata());

        $args->setFoundMetadata(null);

        self::assertNull($args->getFoundMetadata());
    }
}
