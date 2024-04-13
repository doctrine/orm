<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\Reflection;

use Doctrine\ORM\Mapping\Reflection\ReflectionPropertiesGetter;
use Doctrine\Persistence\Mapping\ReflectionService;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\Reflection\ClassWithMixedProperties;
use Doctrine\Tests\Models\Reflection\ParentClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

        self::assertCount(5, $properties);

        foreach ($properties as $property) {
            self::assertInstanceOf('ReflectionProperty', $property);
        }
    }

    public function testRetrievedInstancesAreNotStatic(): void
    {
        $properties = (new ReflectionPropertiesGetter(new RuntimeReflectionService()))
            ->getProperties(ClassWithMixedProperties::class);

        foreach ($properties as $property) {
            self::assertFalse($property->isStatic());
        }
    }

    public function testExpectedKeys(): void
    {
        $properties = (new ReflectionPropertiesGetter(new RuntimeReflectionService()))
            ->getProperties(ClassWithMixedProperties::class);

        self::assertArrayHasKey(
            "\0" . ClassWithMixedProperties::class . "\0" . 'privateProperty',
            $properties
        );
        self::assertArrayHasKey(
            "\0" . ClassWithMixedProperties::class . "\0" . 'privatePropertyOverride',
            $properties
        );
        self::assertArrayHasKey(
            "\0" . ParentClass::class . "\0" . 'privatePropertyOverride',
            $properties
        );
        self::assertArrayHasKey(
            "\0*\0protectedProperty",
            $properties
        );
        self::assertArrayHasKey(
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
            self::assertSame($property->getName(), $property->getValue($object));
        }
    }

    public function testPropertyGetterIsIdempotent(): void
    {
        $getter = (new ReflectionPropertiesGetter(new RuntimeReflectionService()));

        self::assertSame(
            $getter->getProperties(ClassWithMixedProperties::class),
            $getter->getProperties(ClassWithMixedProperties::class)
        );
    }

    public function testPropertyGetterWillSkipPropertiesNotRetrievedByTheRuntimeReflectionService(): void
    {
        $reflectionService = $this->createMock(ReflectionService::class);
        assert($reflectionService instanceof ReflectionService || $reflectionService instanceof MockObject);

        $reflectionService
            ->expects(self::exactly(2))
            ->method('getClass')
            ->with(self::logicalOr(ClassWithMixedProperties::class, ParentClass::class))
            ->will(self::returnValueMap([
                [ClassWithMixedProperties::class, new ReflectionClass(ClassWithMixedProperties::class)],
                [ParentClass::class, new ReflectionClass(ParentClass::class)],
            ]));

        $reflectionService
            ->expects(self::atLeastOnce())
            ->method('getAccessibleProperty');

        $getter = (new ReflectionPropertiesGetter($reflectionService));

        self::assertEmpty($getter->getProperties(ClassWithMixedProperties::class));
    }

    public function testPropertyGetterWillSkipClassesNotRetrievedByTheRuntimeReflectionService(): void
    {
        $reflectionService = $this->createMock(ReflectionService::class);
        assert($reflectionService instanceof ReflectionService || $reflectionService instanceof MockObject);

        $reflectionService
            ->expects(self::once())
            ->method('getClass')
            ->with(ClassWithMixedProperties::class);

        $reflectionService->expects(self::never())->method('getAccessibleProperty');

        $getter = (new ReflectionPropertiesGetter($reflectionService));

        self::assertEmpty($getter->getProperties(ClassWithMixedProperties::class));
    }
}
