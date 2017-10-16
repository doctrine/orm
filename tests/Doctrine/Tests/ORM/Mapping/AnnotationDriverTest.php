<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\DDC1872\DDC1872ExampleEntityWithoutOverride;
use Doctrine\Tests\Models\DDC1872\DDC1872ExampleEntityWithOverride;
use Doctrine\Tests\Models\DirectoryTree\Directory;
use Doctrine\Tests\Models\DirectoryTree\File;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;

class AnnotationDriverTest extends AbstractMappingDriverTest
{
    /**
     * @group DDC-268
     */
    public function testLoadMetadataForNonEntityThrowsException()
    {
        $cm = new ClassMetadata('stdClass');
        $cm->initializeReflection(new RuntimeReflectionService());
        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $annotationDriver->loadMetadataForClass('stdClass', $cm);
    }

    /**
     * @expectedException Doctrine\ORM\Cache\CacheException
     * @expectedExceptionMessage Entity association field "Doctrine\Tests\ORM\Mapping\AnnotationSLC#foo" not configured as part of the second-level cache.
     */
    public function testFailingSecondLevelCacheAssociation()
    {
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata(AnnotationSLC::class);
        $mappingDriver->loadMetadataForClass(AnnotationSLC::class, $class);
    }

    /**
     * @group DDC-268
     */
    public function testColumnWithMissingTypeDefaultsToString()
    {
        $cm = new ClassMetadata(ColumnWithoutType::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $annotationDriver = $this->_loadDriver();

        $annotationDriver->loadMetadataForClass(Mapping\InvalidColumn::class, $cm);
        $this->assertEquals('string', $cm->fieldMappings['id']['type']);
    }

    /**
     * @group DDC-318
     */
    public function testGetAllClassNamesIsIdempotent()
    {
        $annotationDriver = $this->_loadDriverForCMSModels();
        $original = $annotationDriver->getAllClassNames();

        $annotationDriver = $this->_loadDriverForCMSModels();
        $afterTestReset = $annotationDriver->getAllClassNames();

        $this->assertEquals($original, $afterTestReset);
    }

    /**
     * @group DDC-318
     */
    public function testGetAllClassNamesIsIdempotentEvenWithDifferentDriverInstances()
    {
        $annotationDriver = $this->_loadDriverForCMSModels();
        $original = $annotationDriver->getAllClassNames();

        $annotationDriver = $this->_loadDriverForCMSModels();
        $afterTestReset = $annotationDriver->getAllClassNames();

        $this->assertEquals($original, $afterTestReset);
    }

    /**
     * @group DDC-318
     */
    public function testGetAllClassNamesReturnsAlreadyLoadedClassesIfAppropriate()
    {
        $this->_ensureIsLoaded(CmsUser::class);

        $annotationDriver = $this->_loadDriverForCMSModels();
        $classes = $annotationDriver->getAllClassNames();

        $this->assertContains(CmsUser::class, $classes);
    }

    /**
     * @group DDC-318
     */
    public function testGetClassNamesReturnsOnlyTheAppropriateClasses()
    {
        $this->_ensureIsLoaded(ECommerceCart::class);

        $annotationDriver = $this->_loadDriverForCMSModels();
        $classes = $annotationDriver->getAllClassNames();

        $this->assertNotContains(ECommerceCart::class, $classes);
    }

    protected function _loadDriverForCMSModels()
    {
        $annotationDriver = $this->_loadDriver();
        $annotationDriver->addPaths([__DIR__ . '/../../Models/CMS/']);
        return $annotationDriver;
    }

    protected function _loadDriver()
    {
        return $this->createAnnotationDriver();
    }

    protected function _ensureIsLoaded($entityClassName)
    {
        new $entityClassName;
    }

    /**
     * @group DDC-671
     *
     * Entities for this test are in AbstractMappingDriverTest
     */
    public function testJoinTablesWithMappedSuperclassForAnnotationDriver()
    {
        $annotationDriver = $this->_loadDriver();
        $annotationDriver->addPaths([__DIR__ . '/../../Models/DirectoryTree/']);

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $classPage = $factory->getMetadataFor(File::class);
        $this->assertEquals(File::class, $classPage->associationMappings['parentDirectory']['sourceEntity']);

        $classDirectory = $factory->getMetadataFor(Directory::class);
        $this->assertEquals(Directory::class, $classDirectory->associationMappings['parentDirectory']['sourceEntity']);
    }

    /**
     * @group DDC-945
     */
    public function testInvalidMappedSuperClassWithManyToManyAssociation()
    {
        $annotationDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            "It is illegal to put an inverse side one-to-many or many-to-many association on " .
            "mapped superclass 'Doctrine\Tests\ORM\Mapping\InvalidMappedSuperClass#users'"
        );

        $usingInvalidMsc = $factory->getMetadataFor(UsingInvalidMappedSuperClass::class);
    }

    /**
     * @group DDC-1050
     */
    public function testInvalidMappedSuperClassWithInheritanceInformation()
    {
        $annotationDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            "It is not supported to define inheritance information on a mapped " .
            "superclass '" . MappedSuperClassInheritence::class . "'."
        );

        $usingInvalidMsc = $factory->getMetadataFor(MappedSuperClassInheritence::class);
    }

    /**
     * @group DDC-1034
     */
    public function testInheritanceSkipsParentLifecycleCallbacks()
    {
        $annotationDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $cm = $factory->getMetadataFor(AnnotationChild::class);
        $this->assertEquals(["postLoad" => ["postLoad"], "preUpdate" => ["preUpdate"]], $cm->lifecycleCallbacks);

        $cm = $factory->getMetadataFor(AnnotationParent::class);
        $this->assertEquals(["postLoad" => ["postLoad"], "preUpdate" => ["preUpdate"]], $cm->lifecycleCallbacks);
    }

    /**
     * @group DDC-1156
     */
    public function testMappedSuperclassInMiddleOfInheritanceHierarchy()
    {
        $annotationDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);

        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        self::assertInstanceOf(ClassMetadata::class, $factory->getMetadataFor(ChildEntity::class));
    }

    public function testInvalidFetchOptionThrowsException()
    {
        $annotationDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $this->expectException(AnnotationException::class);
        $this->expectExceptionMessage('[Enum Error] Attribute "fetch" of @Doctrine\ORM\Mapping\OneToMany declared on property Doctrine\Tests\ORM\Mapping\InvalidFetchOption::$collection accept only [LAZY, EAGER, EXTRA_LAZY], but got eager.');

        $factory->getMetadataFor(InvalidFetchOption::class);
    }

    public function testAttributeOverridesMappingWithTrait()
    {
        $factory       = $this->createClassMetadataFactory();

        $metadataWithoutOverride = $factory->getMetadataFor(DDC1872ExampleEntityWithoutOverride::class);
        $metadataWithOverride = $factory->getMetadataFor(DDC1872ExampleEntityWithOverride::class);

        $this->assertEquals('trait_foo', $metadataWithoutOverride->fieldMappings['foo']['columnName']);
        $this->assertEquals('foo_overridden', $metadataWithOverride->fieldMappings['foo']['columnName']);
        $this->assertArrayHasKey('example_trait_bar_id', $metadataWithoutOverride->associationMappings['bar']['joinColumnFieldNames']);
        $this->assertArrayHasKey('example_entity_overridden_bar_id', $metadataWithOverride->associationMappings['bar']['joinColumnFieldNames']);
    }
}

/**
 * @Entity
 */
class ColumnWithoutType
{
    /** @Id @Column */
    public $id;
}

/**
 * @MappedSuperclass
 */
class InvalidMappedSuperClass
{
    /**
     * @ManyToMany(targetEntity="Doctrine\Tests\Models\CMS\CmsUser", mappedBy="invalid")
     */
    private $users;
}

/**
 * @Entity
 */
class UsingInvalidMappedSuperClass extends InvalidMappedSuperClass
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
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
     * @Id @Column(type="integer") @GeneratedValue
     */
    private $id;

    /**
     * @PostLoad
     */
    public function postLoad()
    {

    }

    /**
     * @PreUpdate
     */
    public function preUpdate()
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
    /** @Id @Column(type="string") */
    private $id;
}

/**
 * @MappedSuperclass
 */
class MiddleMappedSuperclass extends SuperEntity
{
    /** @Column(type="string") */
    private $name;
}

/**
 * @Entity
 */
class ChildEntity extends MiddleMappedSuperclass
{
    /**
     * @Column(type="string")
     */
    private $text;
}

/**
 * @Entity
 */
class InvalidFetchOption
{
    /**
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
     * @Id
     * @ManyToOne(targetEntity="AnnotationSLCFoo")
     */
    public $foo;
}
/**
 * @Entity
 */
class AnnotationSLCFoo
{
    /**
     * @Column(type="string")
     */
    public $id;
}
