<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\Tests\OrmTestCase;

/**
 * @group DDC-1955
 */
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

        $this->assertInstanceOf($className, $object);
        $this->assertSame($object, $this->resolver->resolve($className));
    }

    public function testRegisterAndResolve(): void
    {
        $className = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $object    = new $className();

        $this->resolver->register($object);

        $this->assertSame($object, $this->resolver->resolve($className));
    }

    public function testClearOne(): void
    {
        $className1 = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $className2 = '\Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener';

        $obj1 = $this->resolver->resolve($className1);
        $obj2 = $this->resolver->resolve($className2);

        $this->assertInstanceOf($className1, $obj1);
        $this->assertInstanceOf($className2, $obj2);

        $this->assertSame($obj1, $this->resolver->resolve($className1));
        $this->assertSame($obj2, $this->resolver->resolve($className2));

        $this->resolver->clear($className1);

        $this->assertInstanceOf($className1, $this->resolver->resolve($className1));
        $this->assertInstanceOf($className2, $this->resolver->resolve($className2));

        $this->assertNotSame($obj1, $this->resolver->resolve($className1));
        $this->assertSame($obj2, $this->resolver->resolve($className2));
    }

    public function testClearAll(): void
    {
        $className1 = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $className2 = '\Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener';

        $obj1 = $this->resolver->resolve($className1);
        $obj2 = $this->resolver->resolve($className2);

        $this->assertInstanceOf($className1, $obj1);
        $this->assertInstanceOf($className2, $obj2);

        $this->assertSame($obj1, $this->resolver->resolve($className1));
        $this->assertSame($obj2, $this->resolver->resolve($className2));

        $this->resolver->clear();

        $this->assertInstanceOf($className1, $this->resolver->resolve($className1));
        $this->assertInstanceOf($className2, $this->resolver->resolve($className2));

        $this->assertNotSame($obj1, $this->resolver->resolve($className1));
        $this->assertNotSame($obj2, $this->resolver->resolve($className2));
    }

    public function testRegisterStringException(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('An object was expected, but got "string".');
        $this->resolver->register('CompanyContractListener');
    }
}
