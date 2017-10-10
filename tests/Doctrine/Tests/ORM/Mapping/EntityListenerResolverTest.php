<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\Tests\OrmTestCase;

/**
 * @group DDC-1955
 */
class EntityListenerResolverTest extends OrmTestCase
{

    /**
     * @var \Doctrine\ORM\Mapping\DefaultEntityListenerResolver
     */
    private $resolver;

    protected function setUp()
    {
        parent::setUp();
        $this->resolver  = new DefaultEntityListenerResolver();
    }

    public function testResolve()
    {
        $className  = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $object     = $this->resolver->resolve($className);

        self::assertInstanceOf($className, $object);
        self::assertSame($object, $this->resolver->resolve($className));
    }

    public function testRegisterAndResolve()
    {
        $className  = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $object     = new $className();

        $this->resolver->register($object);

        self::assertSame($object, $this->resolver->resolve($className));
    }

    public function testClearOne()
    {
        $className1  = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $className2  = '\Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener';

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

    public function testClearAll()
    {
        $className1  = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $className2  = '\Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener';

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

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage An object was expected, but got "string".
     */
    public function testRegisterStringException()
    {
        $this->resolver->register('CompanyContractListener');
    }
}