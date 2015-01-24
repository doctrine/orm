<?php

namespace Doctrine\Tests\ORM\Mapping\Reflection;

use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\Reflection\ReflectionPropertiesGetter;
use Doctrine\Tests\Models\Reflection\ClassWithMixedProperties;
use Doctrine\Tests\Models\Reflection\ParentClass;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

/**
 * Tests for {@see \Doctrine\ORM\Mapping\Reflection\ReflectionPropertiesGetter}
 *
 * @covers \Doctrine\ORM\Mapping\Reflection\ReflectionPropertiesGetter
 */
class ReflectionPropertiesGetterTest extends PHPUnit_Framework_TestCase
{
    public function testRetrievesProperties()
    {
        $properties = (new ReflectionPropertiesGetter(new RuntimeReflectionService()))
            ->getProperties(ClassWithMixedProperties::CLASSNAME);

        $this->assertCount(5, $properties);

        foreach ($properties as $property) {
            $this->assertInstanceOf('ReflectionProperty', $property);
        }
    }

    public function testRetrievedInstancesAreNotStatic()
    {
        $properties = (new ReflectionPropertiesGetter(new RuntimeReflectionService()))
            ->getProperties(ClassWithMixedProperties::CLASSNAME);

        foreach ($properties as $property) {
            $this->assertFalse($property->isStatic());
        }
    }

    public function testExpectedKeys()
    {
        $properties = (new ReflectionPropertiesGetter(new RuntimeReflectionService()))
            ->getProperties(ClassWithMixedProperties::CLASSNAME);

        $this->assertArrayHasKey(
            "\0" . ClassWithMixedProperties::CLASSNAME . "\0" . 'privateProperty',
            $properties
        );
        $this->assertArrayHasKey(
            "\0" . ClassWithMixedProperties::CLASSNAME . "\0" . 'privatePropertyOverride',
            $properties
        );
        $this->assertArrayHasKey(
            "\0" . ParentClass::CLASSNAME . "\0" . 'privatePropertyOverride',
            $properties
        );
        $this->assertArrayHasKey(
            "\0*\0protectedProperty",
            $properties
        );
        $this->assertArrayHasKey(
            "publicProperty",
            $properties
        );
    }

    public function testPropertiesAreAccessible()
    {
        $object     = new ClassWithMixedProperties();
        $properties = (new ReflectionPropertiesGetter(new RuntimeReflectionService()))
            ->getProperties(ClassWithMixedProperties::CLASSNAME);

        foreach ($properties as $property) {
            $this->assertSame($property->getName(), $property->getValue($object));
        }
    }

    public function testPropertyGetterIsIdempotent()
    {
        $getter = (new ReflectionPropertiesGetter(new RuntimeReflectionService()));

        $this->assertSame(
            $getter->getProperties(ClassWithMixedProperties::CLASSNAME),
            $getter->getProperties(ClassWithMixedProperties::CLASSNAME)
        );
    }

    public function testPropertyGetterWillSkipPropertiesNotRetrievedByTheRuntimeReflectionService()
    {
        /* @var $reflectionService ReflectionService|\PHPUnit_Framework_MockObject_MockObject */
        $reflectionService = $this->getMock('Doctrine\Common\Persistence\Mapping\ReflectionService');

        $reflectionService
            ->expects($this->exactly(2))
            ->method('getClass')
            ->with($this->logicalOr(ClassWithMixedProperties::CLASSNAME, ParentClass::CLASSNAME))
            ->will($this->returnValueMap([
                [ClassWithMixedProperties::CLASSNAME, new ReflectionClass(ClassWithMixedProperties::CLASSNAME)],
                [ParentClass::CLASSNAME, new ReflectionClass(ParentClass::CLASSNAME)],
            ]));

        $reflectionService
            ->expects($this->atLeastOnce())
            ->method('getAccessibleProperty');

        $getter = (new ReflectionPropertiesGetter($reflectionService));

        $this->assertEmpty($getter->getProperties(ClassWithMixedProperties::CLASSNAME));
    }

    public function testPropertyGetterWillSkipClassesNotRetrievedByTheRuntimeReflectionService()
    {
        /* @var $reflectionService ReflectionService|\PHPUnit_Framework_MockObject_MockObject */
        $reflectionService = $this->getMock('Doctrine\Common\Persistence\Mapping\ReflectionService');

        $reflectionService
            ->expects($this->once())
            ->method('getClass')
            ->with(ClassWithMixedProperties::CLASSNAME);

        $reflectionService->expects($this->never())->method('getAccessibleProperty');

        $getter = (new ReflectionPropertiesGetter($reflectionService));

        $this->assertEmpty($getter->getProperties(ClassWithMixedProperties::CLASSNAME));
    }
}
