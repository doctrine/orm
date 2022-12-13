<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\Tests\OrmTestCase;

/** @group DDC-1955 */
class EntityListenerResolverTest extends OrmTestCase
{
    /** @var DefaultEntityListenerResolver */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new DefaultEntityListenerResolver();
    }

    public function testResolve(): void
    {
        $className = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $object    = $this->resolver->resolve($className);

        self::assertInstanceOf($className, $object);
        self::assertSame($object, $this->resolver->resolve($className));
    }

    public function testRegisterAndResolve(): void
    {
        $className = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $object    = new $className();

        $this->resolver->register($object);

        self::assertSame($object, $this->resolver->resolve($className));
    }

    public function testClearOne(): void
    {
        $className1 = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $className2 = '\Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener';

        $obj1 = $this->resolver->resolve($className1);
        $obj2 = $this->resolver->resolve($className2);

        self::assertInstanceOf($className1, $obj1);
        self::assertInstanceOf($className2, $obj2);

        self::assertSame($obj1, $this->resolver->resolve($className1));
        self::assertSame($obj2, $this->resolver->resolve($className2));

        $this->resolver->clear($className1);

        self::assertInstanceOf($className1, $this->resolver->resolve($className1));
        self::assertInstanceOf($className2, $this->resolver->resolve($className2));

        self::assertNotSame($obj1, $this->resolver->resolve($className1));
        self::assertSame($obj2, $this->resolver->resolve($className2));
    }

    public function testClearAll(): void
    {
        $className1 = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $className2 = '\Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener';

        $obj1 = $this->resolver->resolve($className1);
        $obj2 = $this->resolver->resolve($className2);

        self::assertInstanceOf($className1, $obj1);
        self::assertInstanceOf($className2, $obj2);

        self::assertSame($obj1, $this->resolver->resolve($className1));
        self::assertSame($obj2, $this->resolver->resolve($className2));

        $this->resolver->clear();

        self::assertInstanceOf($className1, $this->resolver->resolve($className1));
        self::assertInstanceOf($className2, $this->resolver->resolve($className2));

        self::assertNotSame($obj1, $this->resolver->resolve($className1));
        self::assertNotSame($obj2, $this->resolver->resolve($className2));
    }

    public function testRegisterStringException(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('An object was expected, but got "string".');
        $this->resolver->register('CompanyContractListener');
    }
}
