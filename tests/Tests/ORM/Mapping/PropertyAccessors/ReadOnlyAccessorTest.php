<?php

namespace Doctrine\Tests\ORM\Mapping\PropertyAccessors;

use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessorFactory;
use Doctrine\ORM\Mapping\PropertyAccessors\ReadonlyAccessor;
use Doctrine\Tests\OrmTestCase;
use LogicException;

class ReadOnlyAccessorTest extends OrmTestCase
{
    public function testReadOnlyProperty(): void
    {
        $object   = new ReadOnlyClass();
        $accessor = PropertyAccessorFactory::createPropertyAccessor(ReadOnlyClass::class, 'property');

        $this->assertInstanceOf(ReadonlyAccessor::class, $accessor);

        $accessor->setValue($object, 1);

        $this->assertEquals($object->property, 1);
        $this->assertEquals(1, $accessor->getValue($object));
    }

    public function testReadOnlyPropertyOnlyOnce(): void
    {
        $object   = new ReadOnlyClass();
        $accessor = PropertyAccessorFactory::createPropertyAccessor(ReadOnlyClass::class, 'property');

        $this->assertInstanceOf(ReadonlyAccessor::class, $accessor);

        $this->expectException(LogicException::class);

        $accessor->setValue($object, 1);
        $accessor->setValue($object, 2);
    }
}

class ReadOnlyClass
{
    public readonly int $property;
}
