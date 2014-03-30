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
     * @group DDC-869
     */
    public function testGetMetadataForSubclassWithMappedSuperclassWithRepository()
    {
        $class = $this->_factory->getMetadataFor('Doctrine\Tests\Models\DDC869\DDC869CreditCardPayment');

        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['value']));
        $this->assertTrue(isset($class->fieldMappings['creditCardNumber']));
        $this->assertEquals($class->customRepositoryClassName, "Doctrine\Tests\Models\DDC869\DDC869PaymentRepository");


        $class = $this->_factory->getMetadataFor('Doctrine\Tests\Models\DDC869\DDC869ChequePayment');

        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['value']));
        $this->assertTrue(isset($class->fieldMappings['serialNumber']));
        $this->assertEquals($class->customRepositoryClassName, "Doctrine\Tests\Models\DDC869\DDC869PaymentRepository");


        // override repositoryClass
        $class = $this->_factory->getMetadataFor('Doctrine\Tests\ORM\Mapping\SubclassWithRepository');

        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['value']));
        $this->assertEquals($class->customRepositoryClassName, "Doctrine\ORM\EntityRepository");
    }

    /**
     * @group DDC-388
     */
    public function testSerializationWithPrivateFieldsFromMappedSuperclass()
    {

        $class = $this->_factory->getMetadataFor(__NAMESPACE__ . '\\EntitySubClass2');

        $class2 = unserialize(serialize($class));
        $class2->wakeupReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        $this->assertTrue(isset($class2->reflFields['mapped1']));
        $this->assertTrue(isset($class2->reflFields['mapped2']));
        $this->assertTrue(isset($class2->reflFields['mappedRelated1']));
    }

    /**
     * @group DDC-1203
     */
    public function testUnmappedSuperclassInHierarchy()
    {
        $class = $this->_factory->getMetadataFor(__NAMESPACE__ . '\\HierarchyD');

        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['a']));
        $this->assertTrue(isset($class->fieldMappings['d']));
    }

    /**
     * @group DDC-1204
     */
    public function testUnmappedEntityInHierarchy()
    {
        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException', "Entity 'Doctrine\Tests\ORM\Mapping\HierarchyBEntity' has to be part of the discriminator map of 'Doctrine\Tests\ORM\Mapping\HierarchyBase' to be properly mapped in the inheritance hierarchy. Alternatively you can make 'Doctrine\Tests\ORM\Mapping\HierarchyBEntity' an abstract class to avoid this exception from occurring.");

        $class = $this->_factory->getMetadataFor(__NAMESPACE__ . '\\HierarchyE');
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
    public function testSequenceDefinitionInHierarchyWithSandwichMappedSuperclass()
    {
        $class = $this->_factory->getMetadataFor(__NAMESPACE__ . '\\HierarchyD');
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
class MappedSuperclassRelated1 {}

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
 *     "c"   = "HierarchyC",
 *     "d"   = "HierarchyD",
 *     "e"   = "HierarchyE"
 * })
 */
abstract class HierarchyBase
{
    /**
     * @Column(type="integer") @Id @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="foo", initialValue=10)
     * @var int
     */
    public $id;
}

/**
 * @MappedSuperclass
 */
abstract class HierarchyASuperclass extends HierarchyBase
{
    /** @Column(type="string") */
    public $a;
}

/**
 * @Entity
 */
class HierarchyBEntity extends HierarchyBase
{
    /** @Column(type="string") */
    public $b;
}

/**
 * @Entity
 */
class HierarchyC extends HierarchyBase
{
    /** @Column(type="string") */
    public $c;
}

/**
 * @Entity
 */
class HierarchyD extends HierarchyASuperclass
{
    /** @Column(type="string") */
    public $d;
}

/**
 * @Entity
 */
class HierarchyE extends HierarchyBEntity
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
     * @SequenceGenerator(sequenceName="foo", initialValue=10)
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

/**
 * @Entity(repositoryClass = "Doctrine\ORM\EntityRepository")
 */
class SubclassWithRepository extends \Doctrine\Tests\Models\DDC869\DDC869Payment
{

}
