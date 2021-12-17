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
     * Data provider
     *
     * @return ReflectionProperty[][]|string[][]
     */
    public function getTestedReflectionProperties(): array
    {
        return [
            [
                $this->getReflectionProperty(BooleanModel::class, 'id'),
                $this->getReflectionProperty(BooleanModel::class, 'id'),
                BooleanModel::class,
            ],
            // reflection on embeddables that have properties defined in abstract ancestors:
            [
                $this->getReflectionProperty(BooleanModel::class, 'id'),
                $this->getReflectionProperty(AbstractEmbeddable::class, 'propertyInAbstractClass'),
                ConcreteEmbeddable::class,
            ],
            [
                $this->getReflectionProperty(BooleanModel::class, 'id'),
                $this->getReflectionProperty(ConcreteEmbeddable::class, 'propertyInConcreteClass'),
                ConcreteEmbeddable::class,
            ],
            // reflection on classes extending internal PHP classes:
            [
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'privateProperty'),
                ArrayObjectExtendingClass::class,
            ],
            [
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'protectedProperty'),
                ArrayObjectExtendingClass::class,
            ],
            [
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                ArrayObjectExtendingClass::class,
            ],
        ];
    }

    private function getReflectionProperty(string $className, string $propertyName): ReflectionProperty
    {
        $reflectionProperty = new ReflectionProperty($className, $propertyName);

        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }
}
