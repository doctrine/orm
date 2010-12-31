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
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $reader = new \Doctrine\Common\Annotations\AnnotationReader($cache);
        $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
        return new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader);
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