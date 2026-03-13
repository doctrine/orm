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
    public function testSetNullOnHookedPropertyDoesNotThrow(): void
    {
        $accessor = PropertyAccessorFactory::createPropertyAccessor(User::class, 'first');

        $object = new User();
        $accessor->setValue($object, 'Alice');
        $this->assertEquals('Alice', $accessor->getValue($object));

        // Setting null on a non-nullable hooked property should not throw.
        // Doctrine calls this when uninitializing an entity (e.g. after delete).
        $accessor->setValue($object, null);

        // Property retains its previous value since unset is not possible on hooked properties.
        $this->assertEquals('Alice', $accessor->getValue($object));
    }
}

class TypedClass
{
    public int $property;
}
