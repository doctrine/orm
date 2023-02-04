<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Cache\Exception\CacheException;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\PostLoad;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver as PersistenceAnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\DDC1872\DDC1872ExampleEntityWithoutOverride;
use Doctrine\Tests\Models\DDC1872\DDC1872ExampleEntityWithOverride;
use Doctrine\Tests\Models\DirectoryTree\Directory;
use Doctrine\Tests\Models\DirectoryTree\File;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Generator;

use function class_exists;
use function is_subclass_of;

class AnnotationDriverTest extends MappingDriverTestCase
{
    /** @group DDC-268 */
    public function testLoadMetadataForNonEntityThrowsException(): void
    {
        $cm = new ClassMetadata('stdClass');
        $cm->initializeReflection(new RuntimeReflectionService());
        $reader           = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $this->expectException(MappingException::class);
        $annotationDriver->loadMetadataForClass('stdClass', $cm);
    }

    public function testFailingSecondLevelCacheAssociation(): void
    {
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('Entity association field "Doctrine\Tests\ORM\Mapping\AnnotationSLC#foo" not configured as part of the second-level cache.');
        $mappingDriver = $this->loadDriver();

        $class = new ClassMetadata(AnnotationSLC::class);
        $mappingDriver->loadMetadataForClass(AnnotationSLC::class, $class);
    }

    /** @group DDC-268 */
    public function testColumnWithMissingTypeDefaultsToString(): void
    {
        $cm = new ClassMetadata(ColumnWithoutType::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $annotationDriver = $this->loadDriver();

        $annotationDriver->loadMetadataForClass(Mapping\InvalidColumn::class, $cm);
        self::assertEquals('string', $cm->fieldMappings['id']['type']);
    }

    /** @group DDC-318 */
    public function testGetAllClassNamesIsIdempotent(): void
    {
        $annotationDriver = $this->loadDriverForCMSModels();
        $original         = $annotationDriver->getAllClassNames();

        $annotationDriver = $this->loadDriverForCMSModels();
        $afterTestReset   = $annotationDriver->getAllClassNames();

        self::assertEquals($original, $afterTestReset);
    }

    /** @group DDC-318 */
    public function testGetAllClassNamesIsIdempotentEvenWithDifferentDriverInstances(): void
    {
        $annotationDriver = $this->loadDriverForCMSModels();
        $original         = $annotationDriver->getAllClassNames();

        $annotationDriver = $this->loadDriverForCMSModels();
        $afterTestReset   = $annotationDriver->getAllClassNames();

        self::assertEquals($original, $afterTestReset);
    }

    /** @group DDC-318 */
    public function testGetAllClassNamesReturnsAlreadyLoadedClassesIfAppropriate(): void
    {
        $this->ensureIsLoaded(CmsUser::class);

        $annotationDriver = $this->loadDriverForCMSModels();
        $classes          = $annotationDriver->getAllClassNames();

        self::assertContains(CmsUser::class, $classes);
    }

    /** @group DDC-318 */
    public function testGetClassNamesReturnsOnlyTheAppropriateClasses(): void
    {
        $this->ensureIsLoaded(ECommerceCart::class);

        $annotationDriver = $this->loadDriverForCMSModels();
        $classes          = $annotationDriver->getAllClassNames();

        self::assertNotContains(ECommerceCart::class, $classes);
    }

    protected function loadDriverForCMSModels(): AnnotationDriver
    {
        $annotationDriver = $this->loadDriver();
        $annotationDriver->addPaths([__DIR__ . '/../../Models/CMS/']);

        return $annotationDriver;
    }

    /** @return AnnotationDriver */
    protected function loadDriver(): MappingDriver
    {
        return $this->createAnnotationDriver();
    }

    /** @psalm-var class-string<object> $entityClassName */
    protected function ensureIsLoaded(string $entityClassName): void
    {
        new $entityClassName();
    }

    /**
     * @group DDC-671
     *
     * Entities for this test are in AbstractMappingDriverTest
     */
    public function testJoinTablesWithMappedSuperclassForAnnotationDriver(): void
    {
        $annotationDriver = $this->loadDriver();
        $annotationDriver->addPaths([__DIR__ . '/../../Models/DirectoryTree/']);

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $classPage = $factory->getMetadataFor(File::class);
        self::assertEquals(File::class, $classPage->associationMappings['parentDirectory']['sourceEntity']);

        $classDirectory = $factory->getMetadataFor(Directory::class);
        self::assertEquals(Directory::class, $classDirectory->associationMappings['parentDirectory']['sourceEntity']);
    }

    /** @group DDC-945 */
    public function testInvalidMappedSuperClassWithManyToManyAssociation(): void
    {
        $annotationDriver = $this->loadDriver();

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'It is illegal to put an inverse side one-to-many or many-to-many association on ' .
            "mapped superclass 'Doctrine\Tests\ORM\Mapping\InvalidMappedSuperClass#users'"
        );

        $factory->getMetadataFor(UsingInvalidMappedSuperClass::class);
    }

    /** @group DDC-1050 */
    public function testInvalidMappedSuperClassWithInheritanceInformation(): void
    {
        $annotationDriver = $this->loadDriver();

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'It is not supported to define inheritance information on a mapped ' .
            "superclass '" . MappedSuperClassInheritence::class . "'."
        );

        $usingInvalidMsc = $factory->getMetadataFor(MappedSuperClassInheritence::class);
    }

    /** @group DDC-1034 */
    public function testInheritanceSkipsParentLifecycleCallbacks(): void
    {
        $annotationDriver = $this->loadDriver();

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $cm = $factory->getMetadataFor(AnnotationChild::class);
        self::assertEquals(['postLoad' => ['postLoad'], 'preUpdate' => ['preUpdate']], $cm->lifecycleCallbacks);

        $cm = $factory->getMetadataFor(AnnotationParent::class);
        self::assertEquals(['postLoad' => ['postLoad'], 'preUpdate' => ['preUpdate']], $cm->lifecycleCallbacks);
    }

    /** @group DDC-1156 */
    public function testMappedSuperclassInMiddleOfInheritanceHierarchy(): void
    {
        $annotationDriver = $this->loadDriver();

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);

        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        self::assertInstanceOf(ClassMetadata::class, $factory->getMetadataFor(ChildEntity::class));
    }

    public function testInvalidFetchOptionThrowsException(): void
    {
        $annotationDriver = $this->loadDriver();

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Entity 'Doctrine\Tests\ORM\Mapping\InvalidFetchOption' has a mapping with invalid fetch mode 'eager'");

        $factory->getMetadataFor(InvalidFetchOption::class);
    }

    public function testAttributeOverridesMappingWithTrait(): void
    {
        $factory = $this->createClassMetadataFactory();

        $metadataWithoutOverride = $factory->getMetadataFor(DDC1872ExampleEntityWithoutOverride::class);
        $metadataWithOverride    = $factory->getMetadataFor(DDC1872ExampleEntityWithOverride::class);

        self::assertEquals('trait_foo', $metadataWithoutOverride->fieldMappings['foo']['columnName']);
        self::assertEquals('foo_overridden', $metadataWithOverride->fieldMappings['foo']['columnName']);
        self::assertArrayHasKey('example_trait_bar_id', $metadataWithoutOverride->associationMappings['bar']['joinColumnFieldNames']);
        self::assertArrayHasKey('example_entity_overridden_bar_id', $metadataWithOverride->associationMappings['bar']['joinColumnFieldNames']);
    }

    /**
     * @psalm-param class-string $class
     *
     * @dataProvider provideDiscriminatorColumnTestcases
     */
    public function testLengthForDiscriminatorColumn(string $class, int $expectedLength): void
    {
        $factory = $this->createClassMetadataFactory();

        $metadata = $factory->getMetadataFor($class);

        self::assertNotNull($metadata->discriminatorColumn);
        self::assertArrayHasKey('length', $metadata->discriminatorColumn);
        self::assertSame($expectedLength, $metadata->discriminatorColumn['length']);
    }

    public static function provideDiscriminatorColumnTestcases(): Generator
    {
        yield [DiscriminatorColumnWithNullLength::class, 255];
        yield [DiscriminatorColumnWithNoLength::class, 255];
        yield [DiscriminatorColumnWithZeroLength::class, 0];
        yield [DiscriminatorColumnWithNonZeroLength::class, 60];
    }

    public function testLegacyInheritance(): void
    {
        if (! class_exists(PersistenceAnnotationDriver::class)) {
            self::markTestSkipped('This test requires doctrine/persistence 2.');
        }

        self::assertTrue(is_subclass_of(AnnotationDriver::class, PersistenceAnnotationDriver::class));
    }
}

/** @Entity */
class ColumnWithoutType
{
    /**
     * @var int
     * @Id
     * @Column
     */
    public $id;
}

/** @MappedSuperclass */
class InvalidMappedSuperClass
{
    /**
     * @psalm-var Collection<int, CmsUser>
     * @ManyToMany(targetEntity="Doctrine\Tests\Models\CMS\CmsUser", mappedBy="invalid")
     */
    private $users;
}

/** @Entity */
class UsingInvalidMappedSuperClass extends InvalidMappedSuperClass
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;
}

/**
 * @MappedSuperclass
 * @InheritanceType("JOINED")
 * @DiscriminatorMap({"test" = "ColumnWithoutType"})
 */
class MappedSuperClassInheritence
{
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorMap({"parent" = "AnnotationParent", "child" = "AnnotationChild"})
 * @HasLifecycleCallbacks
 */
class AnnotationParent
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /** @PostLoad */
    public function postLoad(): void
    {
    }

    /** @PreUpdate */
    public function preUpdate(): void
    {
    }
}

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class AnnotationChild extends AnnotationParent
{
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({"s"="SuperEntity", "c"="ChildEntity"})
 */
class SuperEntity
{
    /**
     * @var string
     * @Id
     * @Column(type="string", length=255)
     */
    private $id;
}

/** @MappedSuperclass */
class MiddleMappedSuperclass extends SuperEntity
{
    /**
     * @var string
     * @Column(type="string", length=255)
     */
    private $name;
}

/** @Entity */
class ChildEntity extends MiddleMappedSuperclass
{
    /**
     * @var string
     * @Column(type="string", length=255)
     */
    private $text;
}

/** @Entity */
class InvalidFetchOption
{
    /**
     * @var CmsUser
     * @OneToMany(targetEntity="Doctrine\Tests\Models\CMS\CmsUser", fetch="eager")
     */
    private $collection;
}

/**
 * @Entity
 * @Cache
 */
class AnnotationSLC
{
    /**
     * @var AnnotationSLCFoo
     * @Id
     * @ManyToOne(targetEntity="AnnotationSLCFoo")
     */
    public $foo;
}
/** @Entity */
class AnnotationSLCFoo
{
    /**
     * @var string
     * @Column(type="string", length=255)
     */
    public $id;
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(
 *     name="type",
 *     type="string",
 *     length=0,
 *     columnDefinition="enum('region','airport','station','poi') NOT NULL",
 * ),
 * @DiscriminatorMap({"s"="SuperEntity", "c"="ChildEntity"})
 */
class DiscriminatorColumnWithZeroLength
{
    /**
     * @var int
     * @Id
     * @Column
     */
    public $id;
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(
 *     name="type",
 *     type="string",
 * ),
 * @DiscriminatorMap({"s"="SuperEntity", "c"="ChildEntity"})
 */
class DiscriminatorColumnWithNoLength
{
    /**
     * @var int
     * @Id
     * @Column
     */
    public $id;
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(
 *     name="type",
 *     type="string",
 *     length=60,
 * ),
 * @DiscriminatorMap({"s"="SuperEntity", "c"="ChildEntity"})
 */
class DiscriminatorColumnWithNonZeroLength
{
    /**
     * @var int
     * @Id
     * @Column
     */
    public $id;
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(
 *     name="type",
 *     type="string",
 *     length=null,
 * ),
 * @DiscriminatorMap({"s"="SuperEntity", "c"="ChildEntity"})
 */
class DiscriminatorColumnWithNullLength
{
    /**
     * @var int
     * @Id
     * @Column
     */
    public $id;
}
