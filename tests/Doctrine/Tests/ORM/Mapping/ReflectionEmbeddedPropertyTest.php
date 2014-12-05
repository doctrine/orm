<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Instantiator\Instantiator;
use Doctrine\ORM\Mapping\ReflectionEmbeddedProperty;
use ReflectionProperty;

/**
 * Tests for {@see \Doctrine\ORM\Mapping\ReflectionEmbeddedProperty}
 *
 * @covers \Doctrine\ORM\Mapping\ReflectionEmbeddedProperty
 */
class ReflectionEmbeddedPropertyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param ReflectionProperty $parentProperty
     * @param ReflectionProperty $childProperty
     *
     * @dataProvider getTestedReflectionProperties
     */
    public function testCanSetAndGetEmbeddedProperty(
        ReflectionProperty $parentProperty,
        ReflectionProperty $childProperty
    ) {
        $embeddedPropertyReflection = new ReflectionEmbeddedProperty(
            $parentProperty,
            $childProperty,
            $childProperty->getDeclaringClass()->getName()
        );

        $instantiator = new Instantiator();

        $object = $instantiator->instantiate($parentProperty->getDeclaringClass()->getName());

        $embeddedPropertyReflection->setValue($object, 'newValue');

        $this->assertSame('newValue', $embeddedPropertyReflection->getValue($object));

        $embeddedPropertyReflection->setValue($object, 'changedValue');

        $this->assertSame('changedValue', $embeddedPropertyReflection->getValue($object));
    }

    /**
     * Data provider
     *
     * @return ReflectionProperty[][]
     */
    public function getTestedReflectionProperties()
    {
        return array(
            array(
                $this->getReflectionProperty(
                    'Doctrine\\Tests\\Models\\Generic\\BooleanModel',
                    'id'
                ),
                $this->getReflectionProperty(
                    'Doctrine\\Tests\\Models\\Generic\\BooleanModel',
                    'id'
                ),
            ),
            // reflection on classes extending internal PHP classes:
            array(
                $this->getReflectionProperty(
                    'Doctrine\\Tests\\Models\\Reflection\\ArrayObjectExtendingClass',
                    'publicProperty'
                ),
                $this->getReflectionProperty(
                    'Doctrine\\Tests\\Models\\Reflection\\ArrayObjectExtendingClass',
                    'privateProperty'
                ),
            ),
            array(
                $this->getReflectionProperty(
                    'Doctrine\\Tests\\Models\\Reflection\\ArrayObjectExtendingClass',
                    'publicProperty'
                ),
                $this->getReflectionProperty(
                    'Doctrine\\Tests\\Models\\Reflection\\ArrayObjectExtendingClass',
                    'protectedProperty'
                ),
            ),
            array(
                $this->getReflectionProperty(
                    'Doctrine\\Tests\\Models\\Reflection\\ArrayObjectExtendingClass',
                    'publicProperty'
                ),
                $this->getReflectionProperty(
                    'Doctrine\\Tests\\Models\\Reflection\\ArrayObjectExtendingClass',
                    'publicProperty'
                ),
            ),
        );
    }

    /**
     * @param string $className
     * @param string $propertyName
     *
     * @return ReflectionProperty
     */
    private function getReflectionProperty($className, $propertyName)
    {
        $reflectionProperty = new ReflectionProperty($className, $propertyName);

        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }
}
