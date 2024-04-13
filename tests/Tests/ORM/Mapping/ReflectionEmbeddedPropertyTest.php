<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Instantiator\Instantiator;
use Doctrine\ORM\Mapping\ReflectionEmbeddedProperty;
use Doctrine\Tests\Models\Generic\BooleanModel;
use Doctrine\Tests\Models\Reflection\AbstractEmbeddable;
use Doctrine\Tests\Models\Reflection\ArrayObjectExtendingClass;
use Doctrine\Tests\Models\Reflection\ConcreteEmbeddable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Tests for {@see \Doctrine\ORM\Mapping\ReflectionEmbeddedProperty}
 *
 * @covers \Doctrine\ORM\Mapping\ReflectionEmbeddedProperty
 */
class ReflectionEmbeddedPropertyTest extends TestCase
{
    /**
     * @param ReflectionProperty $parentProperty  property of the embeddable/entity where to write the embeddable to
     * @param ReflectionProperty $childProperty   property of the embeddable class where to write values to
     * @param string             $embeddableClass name of the embeddable class to be instantiated
     *
     * @dataProvider getTestedReflectionProperties
     */
    public function testCanSetAndGetEmbeddedProperty(
        ReflectionProperty $parentProperty,
        ReflectionProperty $childProperty,
        string $embeddableClass
    ): void {
        $embeddedPropertyReflection = new ReflectionEmbeddedProperty($parentProperty, $childProperty, $embeddableClass);

        $instantiator = new Instantiator();

        $object = $instantiator->instantiate($parentProperty->getDeclaringClass()->getName());

        $embeddedPropertyReflection->setValue($object, 'newValue');

        self::assertSame('newValue', $embeddedPropertyReflection->getValue($object));

        $embeddedPropertyReflection->setValue($object, 'changedValue');

        self::assertSame('changedValue', $embeddedPropertyReflection->getValue($object));
    }

    /**
     * @param ReflectionProperty $parentProperty  property of the embeddable/entity where to write the embeddable to
     * @param ReflectionProperty $childProperty   property of the embeddable class where to write values to
     * @param string             $embeddableClass name of the embeddable class to be instantiated
     *
     * @dataProvider getTestedReflectionProperties
     */
    public function testWillSkipReadingPropertiesFromNullEmbeddable(
        ReflectionProperty $parentProperty,
        ReflectionProperty $childProperty,
        string $embeddableClass
    ): void {
        $embeddedPropertyReflection = new ReflectionEmbeddedProperty($parentProperty, $childProperty, $embeddableClass);

        $instantiator = new Instantiator();

        self::assertNull($embeddedPropertyReflection->getValue(
            $instantiator->instantiate($parentProperty->getDeclaringClass()->getName())
        ));
    }

    /**
     * @return ReflectionProperty[][]|string[][]
     */
    public static function getTestedReflectionProperties(): array
    {
        return [
            [
                self::getReflectionProperty(BooleanModel::class, 'id'),
                self::getReflectionProperty(BooleanModel::class, 'id'),
                BooleanModel::class,
            ],
            // reflection on embeddables that have properties defined in abstract ancestors:
            [
                self::getReflectionProperty(BooleanModel::class, 'id'),
                self::getReflectionProperty(AbstractEmbeddable::class, 'propertyInAbstractClass'),
                ConcreteEmbeddable::class,
            ],
            [
                self::getReflectionProperty(BooleanModel::class, 'id'),
                self::getReflectionProperty(ConcreteEmbeddable::class, 'propertyInConcreteClass'),
                ConcreteEmbeddable::class,
            ],
            // reflection on classes extending internal PHP classes:
            [
                self::getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                self::getReflectionProperty(ArrayObjectExtendingClass::class, 'privateProperty'),
                ArrayObjectExtendingClass::class,
            ],
            [
                self::getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                self::getReflectionProperty(ArrayObjectExtendingClass::class, 'protectedProperty'),
                ArrayObjectExtendingClass::class,
            ],
            [
                self::getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                self::getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                ArrayObjectExtendingClass::class,
            ],
        ];
    }

    private static function getReflectionProperty(string $className, string $propertyName): ReflectionProperty
    {
        $reflectionProperty = new ReflectionProperty($className, $propertyName);

        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }
}
