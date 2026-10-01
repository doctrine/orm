<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\PropertyAccessors;

use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessorFactory;
use Doctrine\ORM\Mapping\PropertyAccessors\TypedNoDefaultPropertyAccessor;
use Doctrine\Tests\Models\PropertyHooks\User;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

class TypedNoDefaultPropertyAccessorTest extends OrmTestCase
{
    public function testSetValueWithoutDefault(): void
    {
        $accessor = PropertyAccessorFactory::createPropertyAccessor(TypedClass::class, 'property');

        $this->assertInstanceOf(TypedNoDefaultPropertyAccessor::class, $accessor);

        $object = new TypedClass();
        $accessor->setValue($object, 42);
        $this->assertEquals(42, $accessor->getValue($object));
    }

    public function testSetNullWithoutDefault(): void
    {
        $accessor = PropertyAccessorFactory::createPropertyAccessor(TypedClass::class, 'property');

        $object = new TypedClass();
        $accessor->setValue($object, null);
        $this->assertNull($accessor->getValue($object));

        $accessor->setValue($object, 42);
        $this->assertEquals(42, $accessor->getValue($object));

        $accessor->setValue($object, null);
        $this->assertNull($accessor->getValue($object));
    }

    #[RequiresPhp('>= 8.4.0')]
    public function testSetNullOnHookedPropertyLeavesItUninitialized(): void
    {
        $accessor = PropertyAccessorFactory::createPropertyAccessor(User::class, 'first');

        $object = new User();
        $accessor->setValue($object, null);

        $this->assertNull($accessor->getValue($object));
    }

    #[RequiresPhp('>= 8.4.0')]
    public function testSetNullOnWrittenHookedPropertyDoesNotThrow(): void
    {
        $accessor = PropertyAccessorFactory::createPropertyAccessor(User::class, 'first');

        $object        = new User();
        $object->first = 'Benjamin';

        // unset() on a hooked property is an Error and null would violate the type, so the
        // value cannot be cleared. Not throwing is what matters: removing an entity whose
        // identifier is hooked goes through here.
        $accessor->setValue($object, null);

        $this->assertSame('Benjamin', $accessor->getValue($object));
    }
}

class TypedClass
{
    public int $property;
}
