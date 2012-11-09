<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;

/**
 * @group DDC-1955
 */
class EntityListenerResolverTest extends \Doctrine\Tests\OrmTestCase
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

        $this->assertInstanceOf($className, $object);
        $this->assertSame($object, $this->resolver->resolve($className));
    }

    public function testRegisterAndResolve()
    {
        $className  = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $object     = new $className();

        $this->resolver->register($object);

        $this->assertSame($object, $this->resolver->resolve($className));
    }

    public function testClearOne()
    {
        $className1  = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $className2  = '\Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener';

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

    public function testClearAll()
    {
        $className1  = '\Doctrine\Tests\Models\Company\CompanyContractListener';
        $className2  = '\Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener';

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

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage An object was expected, but got "string".
     */
    public function testRegisterStringException()
    {
        $this->resolver->register('CompanyContractListener');
    }
}