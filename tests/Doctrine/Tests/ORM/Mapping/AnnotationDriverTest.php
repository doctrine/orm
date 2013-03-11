<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Events;

require_once __DIR__ . '/../../TestInit.php';

class AnnotationDriverTest extends AbstractMappingDriverTest
{
    /**
     * @group DDC-268
     */
    public function testLoadMetadataForNonEntityThrowsException()
    {
        $cm = new ClassMetadata('stdClass');
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $reader = new \Doctrine\Common\Annotations\AnnotationReader(new \Doctrine\Common\Cache\ArrayCache());
        $annotationDriver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader);

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');
        $annotationDriver->loadMetadataForClass('stdClass', $cm);
    }

    /**
     * @group DDC-268
     */
    public function testColumnWithMissingTypeDefaultsToString()
    {
        $cm = new ClassMetadata('Doctrine\Tests\ORM\Mapping\ColumnWithoutType');
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $annotationDriver = $this->_loadDriver();

        $annotationDriver->loadMetadataForClass('Doctrine\Tests\ORM\Mapping\InvalidColumn', $cm);
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
        $rightClassName = 'Doctrine\Tests\Models\CMS\CmsUser';
        $this->_ensureIsLoaded($rightClassName);

        $annotationDriver = $this->_loadDriverForCMSModels();
        $classes = $annotationDriver->getAllClassNames();

        $this->assertContains($rightClassName, $classes);
    }

    /**
     * @group DDC-318
     */
    public function testGetClassNamesReturnsOnlyTheAppropriateClasses()
    {
        $extraneousClassName = 'Doctrine\Tests\Models\ECommerce\ECommerceCart';
        $this->_ensureIsLoaded($extraneousClassName);

        $annotationDriver = $this->_loadDriverForCMSModels();
        $classes = $annotationDriver->getAllClassNames();

        $this->assertNotContains($extraneousClassName, $classes);
    }

    protected function _loadDriverForCMSModels()
    {
        $annotationDriver = $this->_loadDriver();
        $annotationDriver->addPaths(array(__DIR__ . '/../../Models/CMS/'));
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
        $annotationDriver->addPaths(array(__DIR__ . '/../../Models/DirectoryTree/'));

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
        $factory->setEntityManager($em);

        $classPage = $factory->getMetadataFor('Doctrine\Tests\Models\DirectoryTree\File');
        $this->assertEquals('Doctrine\Tests\Models\DirectoryTree\File', $classPage->associationMappings['parentDirectory']['sourceEntity']);

        $classDirectory = $factory->getMetadataFor('Doctrine\Tests\Models\DirectoryTree\Directory');
        $this->assertEquals('Doctrine\Tests\Models\DirectoryTree\Directory', $classDirectory->associationMappings['parentDirectory']['sourceEntity']);
    }

    /**
     * @group DDC-945
     */
    public function testInvalidMappedSuperClassWithManyToManyAssociation()
    {
        $annotationDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
        $factory->setEntityManager($em);

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException',
            "It is illegal to put an inverse side one-to-many or many-to-many association on ".
            "mapped superclass 'Doctrine\Tests\ORM\Mapping\InvalidMappedSuperClass#users'");
        $usingInvalidMsc = $factory->getMetadataFor('Doctrine\Tests\ORM\Mapping\UsingInvalidMappedSuperClass');
    }

    /**
     * @group DDC-1050
     */
    public function testInvalidMappedSuperClassWithInheritanceInformation()
    {
        $annotationDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
        $factory->setEntityManager($em);

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException',
            "Its not supported to define inheritance information on a mapped ".
            "superclass 'Doctrine\Tests\ORM\Mapping\MappedSuperClassInheritence'.");
        $usingInvalidMsc = $factory->getMetadataFor('Doctrine\Tests\ORM\Mapping\MappedSuperClassInheritence');
    }

    /**
     * @group DDC-1034
     */
    public function testInheritanceSkipsParentLifecycleCallbacks()
    {
        $annotationDriver = $this->_loadDriver();

        $cm = new ClassMetadata('Doctrine\Tests\ORM\Mapping\AnnotationChild');
        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
        $factory->setEntityManager($em);

        $cm = $factory->getMetadataFor('Doctrine\Tests\ORM\Mapping\AnnotationChild');
        $this->assertEquals(array("postLoad" => array("postLoad"), "preUpdate" => array("preUpdate")), $cm->lifecycleCallbacks);

        $cm = $factory->getMetadataFor('Doctrine\Tests\ORM\Mapping\AnnotationParent');
        $this->assertEquals(array("postLoad" => array("postLoad"), "preUpdate" => array("preUpdate")), $cm->lifecycleCallbacks);
    }

    /**
     * @group DDC-1156
     */
    public function testMappedSuperclassInMiddleOfInheritanceHierarchy()
    {
        $annotationDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
        $factory->setEntityManager($em);

        $cm = $factory->getMetadataFor('Doctrine\Tests\ORM\Mapping\ChildEntity');
    }

    public function testInvalidFetchOptionThrowsException()
    {
        $annotationDriver = $this->_loadDriver();

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
        $factory->setEntityManager($em);

        $this->setExpectedException('Doctrine\Common\Annotations\AnnotationException',
            '[Enum Error] Attribute "fetch" of @Doctrine\ORM\Mapping\OneToMany declared on property Doctrine\Tests\ORM\Mapping\InvalidFetchOption::$collection accept only [LAZY, EAGER, EXTRA_LAZY], but got eager.');
        $cm = $factory->getMetadataFor('Doctrine\Tests\ORM\Mapping\InvalidFetchOption');
    }

    public function testAttributeOverridesMappingWithTrait()
    {
        if (!version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $this->markTestSkipped('This test is only for 5.4+.');
        }

        $factory       = $this->createClassMetadataFactory();

        $metadataWithoutOverride = $factory->getMetadataFor('Doctrine\Tests\Models\DDC1872\DDC1872ExampleEntityWithoutOverride');
        $metadataWithOverride = $factory->getMetadataFor('Doctrine\Tests\Models\DDC1872\DDC1872ExampleEntityWithOverride');

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