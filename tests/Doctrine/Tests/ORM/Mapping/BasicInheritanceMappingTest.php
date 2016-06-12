<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Tests\Models\DDC869\DDC869ChequePayment;
use Doctrine\Tests\Models\DDC869\DDC869CreditCardPayment;
use Doctrine\Tests\Models\DDC869\DDC869Payment;
use Doctrine\Tests\Models\DDC869\DDC869PaymentRepository;
use Doctrine\Tests\OrmTestCase;

class BasicInheritanceMappingTest extends OrmTestCase
{
    /**
     * @var ClassMetadataFactory
     */
    private $cmf;

    /**
     * {@inheritDoc}
     */
    protected function setUp() {
        $this->cmf = new ClassMetadataFactory();

        $this->cmf->setEntityManager($this->_getTestEntityManager());
    }

    public function testGetMetadataForTransientClassThrowsException()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $this->cmf->getMetadataFor(TransientBaseClass::class);
    }

    public function testGetMetadataForSubclassWithTransientBaseClass()
    {
        $class = $this->cmf->getMetadataFor(EntitySubClass::class);

        self::assertEmpty($class->subClasses);
        self::assertEmpty($class->parentClasses);

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('name'));
    }

    public function testGetMetadataForSubclassWithMappedSuperclass()
    {
        $class = $this->cmf->getMetadataFor(EntitySubClass2::class);

        self::assertEmpty($class->subClasses);
        self::assertEmpty($class->parentClasses);

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('name'));
        self::assertNotNull($class->getProperty('mapped1'));
        self::assertNotNull($class->getProperty('mapped2'));

        self::assertTrue($class->isInheritedProperty('mapped1'));
        self::assertTrue($class->isInheritedProperty('mapped2'));

        self::assertNull($class->getProperty('transient'));

        self::assertArrayHasKey('mappedRelated1', $class->associationMappings);
    }

    /**
     * @group DDC-869
     */
    public function testGetMetadataForSubclassWithMappedSuperclassWithRepository()
    {
        $class = $this->cmf->getMetadataFor(DDC869CreditCardPayment::class);

        self::assertEquals($class->customRepositoryClassName, DDC869PaymentRepository::class);

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('value'));
        self::assertNotNull($class->getProperty('creditCardNumber'));


        $class = $this->cmf->getMetadataFor(DDC869ChequePayment::class);

        self::assertEquals($class->customRepositoryClassName, DDC869PaymentRepository::class);

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('value'));
        self::assertNotNull($class->getProperty('serialNumber'));

        // override repositoryClass
        $class = $this->cmf->getMetadataFor(SubclassWithRepository::class);

        self::assertEquals($class->customRepositoryClassName, EntityRepository::class);

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('value'));
    }

    /**
     * @group DDC-388
     */
    public function testSerializationWithPrivateFieldsFromMappedSuperclass()
    {
        $class  = $this->cmf->getMetadataFor(EntitySubClass2::class);
        $class2 = unserialize(serialize($class));

        $class2->wakeupReflection(new RuntimeReflectionService);

        self::assertArrayHasKey('mapped1', $class2->reflFields);
        self::assertArrayHasKey('mapped2', $class2->reflFields);
        self::assertArrayHasKey('mappedRelated1', $class2->reflFields);
    }

    /**
     * @group DDC-1203
     */
    public function testUnmappedSuperclassInHierarchy()
    {
        $class = $this->cmf->getMetadataFor(HierarchyD::class);

        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('a'));
        self::assertNotNull($class->getProperty('d'));
    }

    /**
     * @group DDC-1204
     */
    public function testUnmappedEntityInHierarchy()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
              'Entity \'Doctrine\Tests\ORM\Mapping\HierarchyBEntity\' has to be part of the discriminator map'
            . ' of \'Doctrine\Tests\ORM\Mapping\HierarchyBase\' to be properly mapped in the inheritance hierarchy.'
            . ' Alternatively you can make \'Doctrine\Tests\ORM\Mapping\HierarchyBEntity\' an abstract class to'
            . ' avoid this exception from occurring.'
        );

        $this->cmf->getMetadataFor(HierarchyE::class);
    }

    /**
     * @group DDC-1204
     * @group DDC-1203
     */
    public function testMappedSuperclassWithId()
    {
        $class = $this->cmf->getMetadataFor(SuperclassEntity::class);

        self::assertNotNull($class->getProperty('id'));
    }

    /**
     * @group DDC-1156
     * @group DDC-1218
     */
    public function testGeneratedValueFromMappedSuperclass()
    {
        /* @var ClassMetadata $class */
        $class = $this->cmf->getMetadataFor(SuperclassEntity::class);

        self::assertInstanceOf(SequenceGenerator::class, $class->idGenerator);
        self::assertEquals(
            ['allocationSize' => 1, 'initialValue' => 10, 'sequenceName' => 'foo'],
            $class->sequenceGeneratorDefinition
        );
    }

    /**
     * @group DDC-1156
     * @group DDC-1218
     */
    public function testSequenceDefinitionInHierarchyWithSandwichMappedSuperclass()
    {
        /* @var ClassMetadata $class */
        $class = $this->cmf->getMetadataFor(HierarchyD::class);

        self::assertInstanceOf(SequenceGenerator::class, $class->idGenerator);
        self::assertEquals(
            ['allocationSize' => 1, 'initialValue' => 10, 'sequenceName' => 'foo'],
            $class->sequenceGeneratorDefinition
        );
    }

    /**
     * @group DDC-1156
     * @group DDC-1218
     */
    public function testMultipleMappedSuperclasses()
    {
        /* @var ClassMetadata $class */
        $class = $this->cmf->getMetadataFor(MediumSuperclassEntity::class);

        self::assertInstanceOf(SequenceGenerator::class, $class->idGenerator);
        self::assertEquals(
            ['allocationSize' => 1, 'initialValue' => 10, 'sequenceName' => 'foo'],
            $class->sequenceGeneratorDefinition
        );
    }

    /**
     * Ensure indexes are inherited from the mapped superclass.
     *
     * @group DDC-3418
     */
    public function testMappedSuperclassIndex()
    {
        /* @var $ClassMetadata class */
        $class = $this->cmf->getMetadataFor(EntityIndexSubClass::class);

        self::assertNotNull($class->getProperty('mapped1'));
        self::assertArrayHasKey('IDX_NAME_INDEX', $class->table['uniqueConstraints']);
        self::assertArrayHasKey('IDX_MAPPED1_INDEX', $class->table['uniqueConstraints']);
        self::assertArrayHasKey('IDX_MAPPED2_INDEX', $class->table['indexes']);
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
 * @MappedSuperclass
 * @Table(
 *  uniqueConstraints={@UniqueConstraint(name="IDX_MAPPED1_INDEX",columns={"mapped1"})},
 *  indexes={@Index(name="IDX_MAPPED2_INDEX", columns={"mapped2"})}
 * )
 */
class MappedSuperclassBaseIndex {
    /** @Column(type="string") */
    private $mapped1;
    /** @Column(type="string") */
    private $mapped2;
}

/** @Entity @Table(uniqueConstraints={@UniqueConstraint(name="IDX_NAME_INDEX",columns={"name"})}) */
class EntityIndexSubClass extends MappedSuperclassBaseIndex
{
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

/** @MappedSuperclass */
abstract class HierarchyASuperclass extends HierarchyBase
{
    /** @Column(type="string") */
    public $a;
}

/** @Entity */
class HierarchyBEntity extends HierarchyBase
{
    /** @Column(type="string") */
    public $b;
}

/** @Entity */
class HierarchyC extends HierarchyBase
{
    /** @Column(type="string") */
    public $c;
}

/** @Entity */
class HierarchyD extends HierarchyASuperclass
{
    /** @Column(type="string") */
    public $d;
}

/** @Entity */
class HierarchyE extends HierarchyBEntity
{
    /** @Column(type="string") */
    public $e;
}

/** @Entity */
class SuperclassEntity extends SuperclassBase
{
}

/** @MappedSuperclass */
abstract class SuperclassBase
{
    /**
     * @Column(type="integer") @Id @GeneratedValue(strategy="SEQUENCE")
     * @SequenceGenerator(sequenceName="foo", initialValue=10)
     */
    public $id;
}

/** @MappedSuperclass */
abstract class MediumSuperclassBase extends SuperclassBase
{
}

/** @Entity */
class MediumSuperclassEntity extends MediumSuperclassBase
{
}

/** @Entity(repositoryClass = "Doctrine\ORM\EntityRepository") */
class SubclassWithRepository extends DDC869Payment
{
}
