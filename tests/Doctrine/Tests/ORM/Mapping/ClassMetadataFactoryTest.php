<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Tests\Mocks\MetadataDriverMock;
use Doctrine\Tests\Mocks\DatabasePlatformMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

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
        $cmf = new ClassMetadataFactoryTestSubject();
        $cmf->setEntityManager($entityManager);
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

        $metadataDriver = $this->createAnnotationDriver(array(__DIR__ . '/../../Models/Global/'));

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
    
    /**
     * @group DDC-1512
     */
    public function testIsTransient()
    {
        $cmf = new ClassMetadataFactory();
        $driver = $this->getMock('Doctrine\ORM\Mapping\Driver\Driver');
        $driver->expects($this->at(0))
               ->method('isTransient')
               ->with($this->equalTo('Doctrine\Tests\Models\CMS\CmsUser'))
               ->will($this->returnValue(true));
        $driver->expects($this->at(1))
               ->method('isTransient')
               ->with($this->equalTo('Doctrine\Tests\Models\CMS\CmsArticle'))
               ->will($this->returnValue(false));
        
        $em = $this->_createEntityManager($driver);
        
        $this->assertTrue($em->getMetadataFactory()->isTransient('Doctrine\Tests\Models\CMS\CmsUser'));
        $this->assertFalse($em->getMetadataFactory()->isTransient('Doctrine\Tests\Models\CMS\CmsArticle'));
    }
    
    /**
     * @group DDC-1512
     */
    public function testIsTransientEntityNamespace()
    {
        $cmf = new ClassMetadataFactory();
        $driver = $this->getMock('Doctrine\ORM\Mapping\Driver\Driver');
        $driver->expects($this->at(0))
               ->method('isTransient')
               ->with($this->equalTo('Doctrine\Tests\Models\CMS\CmsUser'))
               ->will($this->returnValue(true));
        $driver->expects($this->at(1))
               ->method('isTransient')
               ->with($this->equalTo('Doctrine\Tests\Models\CMS\CmsArticle'))
               ->will($this->returnValue(false));
        
        $em = $this->_createEntityManager($driver);
        $em->getConfiguration()->addEntityNamespace('CMS', 'Doctrine\Tests\Models\CMS');
        
        $this->assertTrue($em->getMetadataFactory()->isTransient('CMS:CmsUser'));
        $this->assertFalse($em->getMetadataFactory()->isTransient('CMS:CmsArticle'));
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
    private $mockMetadata = array();
    private $requestedClasses = array();

    /** @override */
    protected function newClassMetadataInstance($className)
    {
        $this->requestedClasses[] = $className;
        if ( ! isset($this->mockMetadata[$className])) {
            throw new InvalidArgumentException("No mock metadata found for class $className.");
        }
        return $this->mockMetadata[$className];
    }

    public function setMetadataForClass($className, $metadata)
    {
        $this->mockMetadata[$className] = $metadata;
    }

    public function getRequestedClasses()
    {
        return $this->requestedClasses;
    }
}

class TestEntity1
{
    private $id;
    private $name;
    private $other;
    private $association;
}
