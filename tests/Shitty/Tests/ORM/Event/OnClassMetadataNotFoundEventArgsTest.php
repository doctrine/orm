<?php

namespace Shitty\Tests\ORM;

use Shitty\ORM\Event\OnClassMetadataNotFoundEventArgs;
use PHPUnit_Framework_TestCase;

/**
 * Tests for {@see \Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs}
 *
 * @covers \Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs
 */
class OnClassMetadataNotFoundEventArgsTest extends PHPUnit_Framework_TestCase
{
    public function testEventArgsMutability()
    {
        /* @var $objectManager \Shitty\Common\Persistence\ObjectManager */
        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $args = new OnClassMetadataNotFoundEventArgs('foo', $objectManager);

        $this->assertSame('foo', $args->getClassName());
        $this->assertSame($objectManager, $args->getObjectManager());

        $this->assertNull($args->getFoundMetadata());

        /* @var $metadata \Shitty\Common\Persistence\Mapping\ClassMetadata */
        $metadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');

        $args->setFoundMetadata($metadata);

        $this->assertSame($metadata, $args->getFoundMetadata());

        $args->setFoundMetadata(null);

        $this->assertNull($args->getFoundMetadata());
    }
}
