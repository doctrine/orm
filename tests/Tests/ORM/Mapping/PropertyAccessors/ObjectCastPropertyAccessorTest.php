<?php

namespace Doctrine\Tests\ORM\Mapping\PropertyAccessors;

use Doctrine\ORM\Mapping\PropertyAccessors\ObjectCastPropertyAccessor;
use Doctrine\ORM\Proxy\InternalProxy;
use Doctrine\Tests\OrmTestCase;

class ObjectCastPropertyAccessorTest extends OrmTestCase
{
    public function testSetGetPublicPropertyValue(): void
    {
        $object   = new ObjectClass();
        $accessor = ObjectCastPropertyAccessor::fromNames(ObjectClass::class, 'property');

        $accessor->setValue($object, 'value');

        $this->assertEquals($object->property, 'value');
        $this->assertEquals('value', $accessor->getValue($object));
    }

    public function testSetGetPrivatePropertyValue(): void
    {
        $object   = new ObjectClass();
        $accessor = ObjectCastPropertyAccessor::fromNames(ObjectClass::class, 'property2');

        $accessor->setValue($object, 'value');

        $this->assertEquals($object->getProperty2(), 'value');
        $this->assertEquals('value', $accessor->getValue($object));
    }

    public function testSetGetInternalProxyValue(): void
    {
        $object   = new ObjectClassInternalProxy();
        $accessor = ObjectCastPropertyAccessor::fromNames(ObjectClassInternalProxy::class, 'property');

        $accessor->setValue($object, 'value');

        $this->assertEquals($object->property, 'value');
        $this->assertEquals('value', $accessor->getValue($object));
        $this->assertFalse($object->isInitialized);
        $this->assertEquals(2, $object->counter);
    }
}

class ObjectClass
{
    public $property;
    private $property2;

    public function getProperty2()
    {
        return $this->property2;
    }
}

class ObjectClassInternalProxy implements InternalProxy
{
    public $property;
    public $isInitialized = false;
    public $counter = 0;

    public function __setInitialized(bool $initialized): void
    {
        $this->isInitialized = $initialized;
        $this->counter++;
    }

    public function __load(): void
    {
    }

    /** Returns whether this proxy is initialized or not. */
    public function __isInitialized(): bool
    {
        return $this->isInitialized;
    }
}
