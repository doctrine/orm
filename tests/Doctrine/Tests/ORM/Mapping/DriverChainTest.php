<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Driver\Driver;
use Doctrine\ORM\Mapping\Driver\DriverChain;

require_once __DIR__ . '/../../TestInit.php';

class DriverChainTest extends \Doctrine\Tests\OrmTestCase
{
    public function testDelegateToMatchingNamespaceDriver()
    {
        $className = 'Doctrine\Tests\ORM\Mapping\DriverChainEntity';
        $classMetadata = new \Doctrine\ORM\Mapping\ClassMetadata($className);

        $chain = new DriverChain();

        $driver1 = $this->getMock('Doctrine\ORM\Mapping\Driver\Driver');
        $driver1->expects($this->never())
                ->method('loadMetadataForClass');
        $driver1->expectS($this->never())
                ->method('isTransient');

        $driver2 = $this->getMock('Doctrine\ORM\Mapping\Driver\Driver');
        $driver2->expects($this->at(0))
                ->method('loadMetadataForClass')
                ->with($this->equalTo($className), $this->equalTo($classMetadata));
        $driver2->expects($this->at(1))
                ->method('isTransient')
                ->with($this->equalTo($className))
                ->will($this->returnValue( true ));

        $chain->addDriver($driver1, 'Doctrine\Tests\Models\Company');
        $chain->addDriver($driver2, 'Doctrine\Tests\ORM\Mapping');

        $chain->loadMetadataForClass($className, $classMetadata);

        $this->assertTrue( $chain->isTransient($className) );
    }

    public function testLoadMetadata_NoDelegatorFound_ThrowsMappingException()
    {
        $className = 'Doctrine\Tests\ORM\Mapping\DriverChainEntity';
        $classMetadata = new \Doctrine\ORM\Mapping\ClassMetadata($className);

        $chain = new DriverChain();
        
        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');
        $chain->loadMetadataForClass($className, $classMetadata);
    }

    public function testGatherAllClassNames()
    {
        $className = 'Doctrine\Tests\ORM\Mapping\DriverChainEntity';
        $classMetadata = new \Doctrine\ORM\Mapping\ClassMetadata($className);

        $chain = new DriverChain();

        $driver1 = $this->getMock('Doctrine\ORM\Mapping\Driver\Driver');
        $driver1->expects($this->once())
                ->method('getAllClassNames')
                ->will($this->returnValue(array('Doctrine\Tests\Models\Company\Foo')));

        $driver2 = $this->getMock('Doctrine\ORM\Mapping\Driver\Driver');
        $driver2->expects($this->once())
                ->method('getAllClassNames')
                ->will($this->returnValue(array('Doctrine\Tests\ORM\Mapping\Bar', 'Doctrine\Tests\ORM\Mapping\Baz', 'FooBarBaz')));

        $chain->addDriver($driver1, 'Doctrine\Tests\Models\Company');
        $chain->addDriver($driver2, 'Doctrine\Tests\ORM\Mapping');

        $this->assertEquals(array(
            'Doctrine\Tests\Models\Company\Foo',
            'Doctrine\Tests\ORM\Mapping\Bar',
            'Doctrine\Tests\ORM\Mapping\Baz'
        ), $chain->getAllClassNames());
    }

    /**
     * @group DDC-706
     */
    public function testIsTransient()
    {        
        $chain = new DriverChain();
        $chain->addDriver($this->createAnnotationDriver(), 'Doctrine\Tests\Models\CMS');

        $this->assertTrue($chain->isTransient('stdClass'), "stdClass isTransient");
        $this->assertFalse($chain->isTransient('Doctrine\Tests\Models\CMS\CmsUser'), "CmsUser is not Transient");
    }
}

class DriverChainEntity
{
    
}