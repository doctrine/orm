<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Tests\Mocks\MetadataDriverMock;
use Doctrine\Tests\Mocks\DatabasePlatformMock;
use Doctrine\ORM\Mapping\ClassMetadata;

require_once __DIR__ . '/../../TestInit.php';

class ClassMetadataFactoryTest extends \Doctrine\Tests\OrmTestCase
{

    public function testGetMetadataForSingleClass()
    {
        $mockPlatform = new DatabasePlatformMock();
        $mockDriver = new MetadataDriverMock();
        $mockPlatform->setPrefersSequences(true);
        $mockPlatform->setPrefersIdentityColumns(false);

        // Self-made metadata
        $cm1 = new ClassMetadata('Doctrine\Tests\ORM\Mapping\TestEntity1');
        // Add a mapped field
        $cm1->mapField(array('fieldName' => 'name', 'type' => 'varchar'));
        // Add a mapped field
        $cm1->mapField(array('fieldName' => 'id', 'type' => 'integer', 'id' => true));
        // and a mapped association
        $cm1->mapOneToOne(array('fieldName' => 'other', 'targetEntity' => 'Other', 'mappedBy' => 'this'));
        // and an id generator type
        $cm1->setIdGeneratorType('auto');

        // SUT
        $cmf = new ClassMetadataFactoryTestSubject($mockDriver, $mockPlatform);
        $cmf->setMetadataForClass('Doctrine\Tests\ORM\Mapping\TestEntity1', $cm1);

        // Prechecks
        $this->assertEquals(array(), $cm1->parentClasses);
        $this->assertEquals('none', $cm1->inheritanceType);
        $this->assertTrue($cm1->hasField('name'));
        $this->assertEquals(1, count($cm1->associationMappings));
        $this->assertEquals('auto', $cm1->generatorType);

        // Go
        $cm1 = $cmf->getMetadataFor('Doctrine\Tests\ORM\Mapping\TestEntity1');

        $this->assertEquals(array(), $cm1->parentClasses);
        $this->assertTrue($cm1->hasField('name'));
        $this->assertEquals('sequence', $cm1->generatorType);
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
}