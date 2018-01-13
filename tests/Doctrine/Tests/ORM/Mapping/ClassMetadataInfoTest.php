<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use PHPUnit\Framework\TestCase;

class ClassMetadataInfoTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @group legacy
     * @expectedDeprecation Class Doctrine\ORM\Mapping\ClassMetadataInfo is deprecated in favor of class Doctrine\ORM\Mapping\ClassMetadata since 2.7, will be removed in 3.0.
     */
    public function testTheClassIsDeprecated() : void
    {
        $this->assertTrue(class_exists(ClassMetadataInfo::class));
    }

    /**
     * @group legacy
     * @expectedDeprecation Class Doctrine\ORM\Mapping\ClassMetadataInfo is deprecated in favor of class Doctrine\ORM\Mapping\ClassMetadata since 2.7, will be removed in 3.0.
     */
    public function testExtendingClassWithOldSignatureStillWorks() : void
    {
        $object = new class () extends ClassMetadataInfoTest {
            public function whatever(ClassMetadataInfo $cm) : bool
            {
                return true;
            }
        };
        $this->assertTrue($object->whatever(new ClassMetadata('MyEntity')));
    }

    public function whatever(ClassMetadata $cm) : bool
    {
        return true;
    }
}
