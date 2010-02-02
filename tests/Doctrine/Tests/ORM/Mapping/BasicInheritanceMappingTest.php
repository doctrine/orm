<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadataFactory;

require_once __DIR__ . '/../../TestInit.php';

class BasicInheritanceMappingTest extends \Doctrine\Tests\OrmTestCase
{
    private $_factory;
    
    protected function setUp() {
        $this->_factory = new ClassMetadataFactory($this->_getTestEntityManager());
    }
    
    /**
     * @expectedException Doctrine\ORM\Mapping\MappingException
     */
    public function testGetMetadataForTransientClassThrowsException()
    {
        $this->_factory->getMetadataFor('Doctrine\Tests\ORM\Mapping\TransientBaseClass');
    }
    
    public function testGetMetadataForSubclassWithTransientBaseClass()
    {
        $class = $this->_factory->getMetadataFor('Doctrine\Tests\ORM\Mapping\EntitySubClass');
        
        $this->assertTrue(empty($class->subClasses));
        $this->assertTrue(empty($class->parentClasses));
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['name']));
    }
    
    public function testGetMetadataForSubclassWithMappedSuperclass()
    {
        $class = $this->_factory->getMetadataFor('Doctrine\Tests\ORM\Mapping\EntitySubClass2');
        
        $this->assertTrue(empty($class->subClasses));
        $this->assertTrue(empty($class->parentClasses));
        
        $this->assertTrue(isset($class->fieldMappings['mapped1']));
        $this->assertTrue(isset($class->fieldMappings['mapped2']));
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['name']));
        
        $this->assertFalse(isset($class->fieldMappings['mapped1']['inherited']));
        $this->assertFalse(isset($class->fieldMappings['mapped2']['inherited']));
        $this->assertFalse(isset($class->fieldMappings['transient']));
        
        $this->assertTrue(empty($class->inheritedAssociationFields));
        $this->assertTrue(isset($class->associationMappings['mappedRelated1']));
    }
}

class TransientBaseClass {
    private $transient1;
    private $transient2;
}

/** @Entity */
class EntitySubClass extends TransientBaseClass
{
    /** @Id @Column(type="integer") */
    private $id;
    /** @Column(type="string") */
    private $name;
}

/** @MappedSuperclass */
class MappedSuperclassBase {
    /** @Column(type="integer") */
    private $mapped1;
    /** @Column(type="string") */
    private $mapped2;
    /**
     * @OneToOne(targetEntity="MappedSuperclassRelated1")
     * @JoinColumn(name="related1_id", referencedColumnName="id")
     */
    private $mappedRelated1;
    private $transient;
}

/** @Entity */
class EntitySubClass2 extends MappedSuperclassBase {
    /** @Id @Column(type="integer") */
    private $id;
    /** @Column(type="string") */
    private $name;
}
