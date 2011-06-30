<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../../TestInit.php';

class BasicInheritanceMappingTest extends \Doctrine\Tests\OrmTestCase
{
    private $_factory;
    
    protected function setUp() {
        $this->_factory = new ClassMetadataFactory();
        $this->_factory->setEntityManager($this->_getTestEntityManager());
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
        
        $this->assertTrue(isset($class->associationMappings['mappedRelated1']));
    }

    /**
     * @group DDC-388
     */
    public function testSerializationWithPrivateFieldsFromMappedSuperclass()
    {

        $class = $this->_factory->getMetadataFor(__NAMESPACE__ . '\\EntitySubClass2');

        $class2 = unserialize(serialize($class));

        $this->assertTrue(isset($class2->reflFields['mapped1']));
        $this->assertTrue(isset($class2->reflFields['mapped2']));
        $this->assertTrue(isset($class2->reflFields['mappedRelated1']));
    }
    
    /**
     * @group DDC-1203
     */
    public function testUnmappedSuperclassInHierachy()
    {
        $class = $this->_factory->getMetadataFor(__NAMESPACE__ . '\\HierachyD');
        
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['a']));
        $this->assertTrue(isset($class->fieldMappings['d']));
    }
    
    /**
     * @group DDC-1204
     */
    public function testUnmappedEntityInHierachy()
    {
        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException', "Entity 'Doctrine\Tests\ORM\Mapping\HierachyBEntity' has to be part of the descriminator map of 'Doctrine\Tests\ORM\Mapping\HierachyBase' to be properly mapped in the inheritance hierachy. Alternatively you can make 'Doctrine\Tests\ORM\Mapping\HierachyBEntity' an abstract class to avoid this exception from occuring.");
        
        $class = $this->_factory->getMetadataFor(__NAMESPACE__ . '\\HierachyE');
    }
    
    /**
     * @group DDC-1204
     * @group DDC-1203
     */
    public function testMappedSuperclassWithId()
    {
        $class = $this->_factory->getMetadataFor(__NAMESPACE__ . '\\SuperclassEntity');
        
        $this->assertTrue(isset($class->fieldMappings['id']));
    }
    
    /**
     * @group DDC-1156
     * @group DDC-1218
     */
    public function testGeneratedValueFromMappedSuperclass()
    {
        $class = $this->_factory->getMetadataFor(__NAMESPACE__ . '\\SuperclassEntity');
        /* @var $class ClassMetadataInfo */
        
        $this->assertInstanceOf('Doctrine\ORM\Id\SequenceGenerator', $class->idGenerator);
        $this->assertEquals(array('allocationSize' => 1, 'initialValue' => 10, 'sequenceName' => 'foo'), $class->sequenceGeneratorDefinition);
    }
    
    /**
     * @group DDC-1156
     * @group DDC-1218
     */
    public function testSequenceDefinitionInHierachyWithSandwichMappedSuperclass()
    {
        $class = $this->_factory->getMetadataFor(__NAMESPACE__ . '\\HierachyD');
        /* @var $class ClassMetadataInfo */
        
        $this->assertInstanceOf('Doctrine\ORM\Id\SequenceGenerator', $class->idGenerator);
        $this->assertEquals(array('allocationSize' => 1, 'initialValue' => 10, 'sequenceName' => 'foo'), $class->sequenceGeneratorDefinition);
    }
    
    /**
     * @group DDC-1156
     * @group DDC-1218
     */
    public function testMultipleMappedSuperclasses()
    {
        $class = $this->_factory->getMetadataFor(__NAMESPACE__ . '\\MediumSuperclassEntity');
        /* @var $class ClassMetadataInfo */
        
        $this->assertInstanceOf('Doctrine\ORM\Id\SequenceGenerator', $class->idGenerator);
        $this->assertEquals(array('allocationSize' => 1, 'initialValue' => 10, 'sequenceName' => 'foo'), $class->sequenceGeneratorDefinition);
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

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string", length=20)
 * @DiscriminatorMap({
 *     "c"   = "HierachyC",
 *     "d"   = "HierachyD",
 *     "e"   = "HierachyE"
 * })
 */
abstract class HierachyBase
{
    /**
     * @Column(type="integer") @Id @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="foo", initialValue="10")
     * @var int
     */
    public $id;
}

/**
 * @MappedSuperclass
 */
abstract class HierachyASuperclass extends HierachyBase
{
    /** @Column(type="string") */
    public $a;
}

/**
 * @Entity
 */
class HierachyBEntity extends HierachyBase
{
    /** @Column(type="string") */
    public $b;
}

/**
 * @Entity
 */
class HierachyC extends HierachyBase
{
    /** @Column(type="string") */
    public $c;
}

/**
 * @Entity
 */
class HierachyD extends HierachyASuperclass
{
    /** @Column(type="string") */
    public $d;
}

/**
 * @Entity
 */
class HierachyE extends HierachyBEntity
{
    /** @Column(type="string") */
    public $e;
}

/**
 * @Entity
 */
class SuperclassEntity extends SuperclassBase
{

}

/**
 * @MappedSuperclass
 */
abstract class SuperclassBase
{
    /**
     * @Column(type="integer") @Id @GeneratedValue(strategy="SEQUENCE") 
     * @SequenceGenerator(sequenceName="foo", initialValue="10")
     * @var int
     */
    public $id;
}

/**
 * @MappedSuperclass
 */
abstract class MediumSuperclassBase extends SuperclassBase
{
    
}

/**
 * @Entity
 */
class MediumSuperclassEntity extends MediumSuperclassBase
{
    
}