<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\PropertyAccessors;

use Doctrine\ORM\Mapping\PropertyAccessors\EnumPropertyAccessor;
use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessorFactory;
use Doctrine\Tests\OrmTestCase;

class EnumPropertyAccessorTest extends OrmTestCase
{
    public function testEnumSetEnumGetValue(): void
    {
        $object   = new EnumClass();
        $accessor = PropertyAccessorFactory::createPropertyAccessor(EnumClass::class, 'enum');

        $accessor = new EnumPropertyAccessor($accessor, EnumType::class);

        $accessor->setValue($object, EnumType::A);

        $this->assertEquals($object->enum, EnumType::A);
        $this->assertEquals(EnumType::A->value, $accessor->getValue($object));
    }

    public function testEnumSetDatabaseGetValue(): void
    {
        $object   = new EnumClass();
        $accessor = PropertyAccessorFactory::createPropertyAccessor(EnumClass::class, 'enum');

        $accessor = new EnumPropertyAccessor($accessor, EnumType::class);

        $accessor->setValue($object, EnumType::A->value);

        $this->assertEquals($object->enum, EnumType::A);
        $this->assertEquals(EnumType::A->value, $accessor->getValue($object));
    }

    public function testEnumSetDatabaseArrayGetValue(): void
    {
        $object   = new EnumClass();
        $accessor = PropertyAccessorFactory::createPropertyAccessor(EnumClass::class, 'enumList');

        $accessor = new EnumPropertyAccessor($accessor, EnumType::class);

        $accessor->setValue($object, $values = [EnumType::A->value, EnumType::B->value, EnumType::C->value]);

        $this->assertEquals($object->enumList, [EnumType::A, EnumType::B, EnumType::C]);
        $this->assertEquals($values, $accessor->getValue($object));
    }
}

class EnumClass
{
    public EnumType $enum;
    public array $enumList;
}

enum EnumType: string
{
    case A = 'a';
    case B = 'b';
    case C = 'c';
}
