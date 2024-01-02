<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Instantiator\Instantiator;
use Doctrine\ORM\Mapping\ReflectionEmbeddedProperty;
use Doctrine\Tests\Models\Generic\BooleanModel;
use Doctrine\Tests\Models\Reflection\AbstractEmbeddable;
use Doctrine\Tests\Models\Reflection\ArrayObjectExtendingClass;
use Doctrine\Tests\Models\Reflection\ConcreteEmbeddable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Tests for {@see \Doctrine\ORM\Mapping\ReflectionEmbeddedProperty}
 */
#[CoversClass(ReflectionEmbeddedProperty::class)]
class ReflectionEmbeddedPropertyTest extends TestCase
{
    /**
     * @param ReflectionProperty $parentProperty  property of the embeddable/entity where to write the embeddable to
     * @param ReflectionProperty $childProperty   property of the embeddable class where to write values to
     * @param string             $embeddableClass name of the embeddable class to be instantiated
     */
    #[DataProvider('getTestedReflectionProperties')]
    public function testCanSetAndGetEmbeddedProperty(
        ReflectionProperty $parentProperty,
        ReflectionProperty $childProperty,
        string $embeddableClass,
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
     */
    #[DataProvider('getTestedReflectionProperties')]
    public function testWillSkipReadingPropertiesFromNullEmbeddable(
        ReflectionProperty $parentProperty,
        ReflectionProperty $childProperty,
        string $embeddableClass,
    ): void {
        $embeddedPropertyReflection = new ReflectionEmbeddedProperty($parentProperty, $childProperty, $embeddableClass);

        $instantiator = new Instantiator();

        self::assertNull($embeddedPropertyReflection->getValue(
            $instantiator->instantiate($parentProperty->getDeclaringClass()->getName()),
        ));
    }

    /** @return ReflectionProperty[][]|string[][] */
    public static function getTestedReflectionProperties(): array
    {
        return [
            [
                new ReflectionProperty(BooleanModel::class, 'id'),
                new ReflectionProperty(BooleanModel::class, 'id'),
                BooleanModel::class,
            ],
            // reflection on embeddables that have properties defined in abstract ancestors:
            [
                new ReflectionProperty(BooleanModel::class, 'id'),
                new ReflectionProperty(AbstractEmbeddable::class, 'propertyInAbstractClass'),
                ConcreteEmbeddable::class,
            ],
            [
                new ReflectionProperty(BooleanModel::class, 'id'),
                new ReflectionProperty(ConcreteEmbeddable::class, 'propertyInConcreteClass'),
                ConcreteEmbeddable::class,
            ],
            // reflection on classes extending internal PHP classes:
            [
                new ReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                new ReflectionProperty(ArrayObjectExtendingClass::class, 'privateProperty'),
                ArrayObjectExtendingClass::class,
            ],
            [
                new ReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                new ReflectionProperty(ArrayObjectExtendingClass::class, 'protectedProperty'),
                ArrayObjectExtendingClass::class,
            ],
            [
                new ReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                new ReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                ArrayObjectExtendingClass::class,
            ],
        ];
    }
}
