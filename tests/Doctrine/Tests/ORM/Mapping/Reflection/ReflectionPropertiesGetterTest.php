<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\Reflection;

use Doctrine\ORM\Mapping\Reflection\ReflectionPropertiesGetter;
use Doctrine\Persistence\Mapping\ReflectionService;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\Reflection\ClassWithMixedProperties;
use Doctrine\Tests\Models\Reflection\ParentClass;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionClass;

use function assert;

/**
 * Tests for {@see \Doctrine\ORM\Mapping\Reflection\ReflectionPropertiesGetter}
 *
 * @covers \Doctrine\ORM\Mapping\Reflection\ReflectionPropertiesGetter
 */
class ReflectionPropertiesGetterTest extends TestCase
{
    public function testRetrievesProperties(): void
    {
        $properties = (new ReflectionPropertiesGetter(new RuntimeReflectionService()))
            ->getProperties(ClassWithMixedProperties::class);

        $this->assertCount(5, $properties);

        foreach ($properties as $property) {
            $this->assertInstanceOf('ReflectionProperty', $property);
        }
    }

    public function testRetrievedInstancesAreNotStatic(): void
    {
        $properties = (new ReflectionPropertiesGetter(new RuntimeReflectionService()))
            ->getProperties(ClassWithMixedProperties::class);

        foreach ($properties as $property) {
            $this->assertFalse($property->isStatic());
        }
    }

    public function testExpectedKeys(): void
    {
        $properties = (new ReflectionPropertiesGetter(new RuntimeReflectionService()))
            ->getProperties(ClassWithMixedProperties::class);

        $this->assertArrayHasKey(
            "\0" . ClassWithMixedProperties::class . "\0" . 'privateProperty',
            $properties
        );
        $this->assertArrayHasKey(
            "\0" . ClassWithMixedProperties::class . "\0" . 'privatePropertyOverride',
            $properties
        );
        $this->assertArrayHasKey(
            "\0" . ParentClass::class . "\0" . 'privatePropertyOverride',
            $properties
        );
        $this->assertArrayHasKey(
            "\0*\0protectedProperty",
            $properties
        );
        $this->assertArrayHasKey(
            'publicProperty',
            $properties
        );
    }

    public function testPropertiesAreAccessible(): void
    {
        $object     = new ClassWithMixedProperties();
        $properties = (new ReflectionPropertiesGetter(new RuntimeReflectionService()))
            ->getProperties(ClassWithMixedProperties::class);

        foreach ($properties as $property) {
            $this->assertSame($property->getName(), $property->getValue($object));
        }
    }

    public function testPropertyGetterIsIdempotent(): void
    {
        $getter = (new ReflectionPropertiesGetter(new RuntimeReflectionService()));

        $this->assertSame(
            $getter->getProperties(ClassWithMixedProperties::class),
            $getter->getProperties(ClassWithMixedProperties::class)
        );
    }

    public function testPropertyGetterWillSkipPropertiesNotRetrievedByTheRuntimeReflectionService(): void
    {
        $reflectionService = $this->createMock(ReflectionService::class);
        assert($reflectionService instanceof ReflectionService || $reflectionService instanceof PHPUnit_Framework_MockObject_MockObject);

        $reflectionService
            ->expects($this->exactly(2))
            ->method('getClass')
            ->with($this->logicalOr(ClassWithMixedProperties::class, ParentClass::class))
            ->will($this->returnValueMap([
                [ClassWithMixedProperties::class, new ReflectionClass(ClassWithMixedProperties::class)],
                [ParentClass::class, new ReflectionClass(ParentClass::class)],
            ]));

        $reflectionService
            ->expects($this->atLeastOnce())
            ->method('getAccessibleProperty');

        $getter = (new ReflectionPropertiesGetter($reflectionService));

        $this->assertEmpty($getter->getProperties(ClassWithMixedProperties::class));
    }

    public function testPropertyGetterWillSkipClassesNotRetrievedByTheRuntimeReflectionService(): void
    {
        $reflectionService = $this->createMock(ReflectionService::class);
        assert($reflectionService instanceof ReflectionService || $reflectionService instanceof PHPUnit_Framework_MockObject_MockObject);

        $reflectionService
            ->expects($this->once())
            ->method('getClass')
            ->with(ClassWithMixedProperties::class);

        $reflectionService->expects($this->never())->method('getAccessibleProperty');

        $getter = (new ReflectionPropertiesGetter($reflectionService));

        $this->assertEmpty($getter->getProperties(ClassWithMixedProperties::class));
    }
}
