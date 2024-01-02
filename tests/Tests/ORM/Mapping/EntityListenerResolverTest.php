<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\Tests\Models\Company\CompanyContractListener;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-1955')]
class EntityListenerResolverTest extends OrmTestCase
{
    private DefaultEntityListenerResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new DefaultEntityListenerResolver();
    }

    public function testResolve(): void
    {
        $className = CompanyContractListener::class;
        $object    = $this->resolver->resolve($className);

        self::assertInstanceOf($className, $object);
        self::assertSame($object, $this->resolver->resolve($className));
    }

    public function testRegisterAndResolve(): void
    {
        $className = CompanyContractListener::class;
        $object    = new $className();

        $this->resolver->register($object);

        self::assertSame($object, $this->resolver->resolve($className));
    }

    public function testClearOne(): void
    {
        $className1 = CompanyContractListener::class;
        $className2 = CompanyFlexUltraContractListener::class;

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
        $className1 = CompanyContractListener::class;
        $className2 = CompanyFlexUltraContractListener::class;

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
}
