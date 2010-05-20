<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Tests\Mocks\MetadataDriverMock;
use Doctrine\Tests\Mocks\DatabasePlatformMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\EventManager;

require_once __DIR__ . '/../../TestInit.php';

class ClassMetadataFactoryTest extends \Doctrine\Tests\OrmTestCase
{
    public function testGetMetadataForSingleClass()
    {
        $mockDriver = new MetadataDriverMock();
        $entityManager = $this->_createEntityManager($mockDriver);

        $conn = $entityManager->getConnection();
        $mockPlatform = $conn->getDatabasePlatform();
        $mockPlatform->setPrefersSequences(true);
        $mockPlatform->setPrefersIdentityColumns(false);

        // Self-made metadata
        $cm1 = new ClassMetadata('Doctrine\Tests\ORM\Mapping\TestEntity1');
        $cm1->setPrimaryTable(array('name' => '`group`'));
        // Add a mapped field
        $cm1->mapField(array('fieldName' => 'name', 'type' => 'varchar'));
        // Add a mapped field
        $cm1->mapField(array('fieldName' => 'id', 'type' => 'integer', 'id' => true));
        // and a mapped association
        $cm1->mapOneToOne(array('fieldName' => 'other', 'targetEntity' => 'Other', 'mappedBy' => 'this'));
        // and an association on the owning side
        $joinColumns = array(
            array('name' => 'other_id', 'referencedColumnName' => 'id')
        );
        $cm1->mapOneToOne(array('fieldName' => 'association', 'targetEntity' => 'Other', 'joinColumns' => $joinColumns));
        // and an id generator type
        $cm1->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        // SUT
        $cmf = new ClassMetadataFactoryTestSubject($entityManager);
        $cmf->setMetadataForClass('Doctrine\Tests\ORM\Mapping\TestEntity1', $cm1);

        // Prechecks
        $this->assertEquals(array(), $cm1->parentClasses);
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm1->inheritanceType);
        $this->assertTrue($cm1->hasField('name'));
        $this->assertEquals(2, count($cm1->associationMappings));
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_AUTO, $cm1->generatorType);

        // Go
        $cm1 = $cmf->getMetadataFor('Doctrine\Tests\ORM\Mapping\TestEntity1');

        $this->assertEquals('group', $cm1->table['name']);
        $this->assertTrue($cm1->table['quoted']);
        $this->assertEquals(array(), $cm1->parentClasses);
        $this->assertTrue($cm1->hasField('name'));
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_SEQUENCE, $cm1->generatorType);
    }

    public function testHasGetMetadata_NamespaceSeperatorIsNotNormalized()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $reader = new \Doctrine\Common\Annotations\AnnotationReader(new \Doctrine\Common\Cache\ArrayCache);
        $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
        $metadataDriver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader);
        $metadataDriver->addPaths(array(__DIR__ . '/../../Models/Global/'));

        $entityManager = $this->_createEntityManager($metadataDriver);

        $mf = $entityManager->getMetadataFactory();
        $m1 = $mf->getMetadataFor("DoctrineGlobal_Article");
        $h1 = $mf->hasMetadataFor("DoctrineGlobal_Article");
        $h2 = $mf->hasMetadataFor("\DoctrineGlobal_Article");
        $m2 = $mf->getMetadataFor("\DoctrineGlobal_Article");

        $this->assertNotSame($m1, $m2);
        $this->assertFalse($h2);
        $this->assertTrue($h1);
    }

    protected function _createEntityManager($metadataDriver)
    {
        $driverMock = new DriverMock();
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(__DIR__ . '/../../Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $eventManager = new EventManager();
        $conn = new ConnectionMock(array(), $driverMock, $config, $eventManager);
        $mockDriver = new MetadataDriverMock();
        $config->setMetadataDriverImpl($metadataDriver);

        return EntityManagerMock::create($conn, $config, $eventManager);
    }
}

/* Test subject class with overriden factory method for mocking purposes */
class ClassMetadataFactoryTestSubject extends \Doctrine\ORM\Mapping\ClassMetadataFactory
{
    private $_mockMetadata = array();
    private $_requestedClasses = array();

    /** @override */
    protected function _newClassMetadataInstance($className)
    {
        $this->_requestedClasses[] = $className;
        if ( ! isset($this->_mockMetadata[$className])) {
            throw new InvalidArgumentException("No mock metadata found for class $className.");
        }
        return $this->_mockMetadata[$className];
    }

    public function setMetadataForClass($className, $metadata)
    {
        $this->_mockMetadata[$className] = $metadata;
    }

    public function getRequestedClasses()
    {
        return $this->_requestedClasses;
    }
}

class TestEntity1
{
    private $id;
    private $name;
    private $other;
    private $association;
}
