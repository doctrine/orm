<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

class YamlMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        if (!class_exists('Symfony\Component\Yaml\Yaml', true)) {
            $this->markTestSkipped('Please install Symfony YAML Component into the include path of your PHP installation.');
        }

        return new YamlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');
    }

    /**
     * @group DDC-671
     *
     * Entities for this test are in AbstractMappingDriverTest
     */
    public function testJoinTablesWithMappedSuperclassForYamlDriver()
    {
        $yamlDriver = $this->_loadDriver();
        $yamlDriver->getLocator()->addPaths(array(__DIR__ . DIRECTORY_SEPARATOR . 'yaml'));

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $classPage = new ClassMetadata('Doctrine\Tests\Models\DirectoryTree\File');
        $classPage = $factory->getMetadataFor('Doctrine\Tests\Models\DirectoryTree\File');
        $this->assertEquals('Doctrine\Tests\Models\DirectoryTree\File', $classPage->associationMappings['parentDirectory']['sourceEntity']);

        $classDirectory = new ClassMetadata('Doctrine\Tests\Models\DirectoryTree\Directory');
        $classDirectory = $factory->getMetadataFor('Doctrine\Tests\Models\DirectoryTree\Directory');
        $this->assertEquals('Doctrine\Tests\Models\DirectoryTree\Directory', $classDirectory->associationMappings['parentDirectory']['sourceEntity']);
    }

    /**
     * @group DDC-1468
     *
     * @expectedException Doctrine\Common\Persistence\Mapping\MappingException
     * @expectedExceptionMessage Invalid mapping file 'Doctrine.Tests.Models.Generic.SerializationModel.dcm.yml' for class 'Doctrine\Tests\Models\Generic\SerializationModel'.
     */
    public function testInvalidMappingFileException()
    {
        $this->createClassMetadata('Doctrine\Tests\Models\Generic\SerializationModel');
    }

    /**
     * @group DDC-2069
     */
    public function testSpacesShouldBeIgnoredWhenUseExplode()
    {
        $metadata = $this->createClassMetadata(__NAMESPACE__.'\DDC2069Entity');
        $unique   = $metadata->table['uniqueConstraints'][0]['columns'];
        $indexes  = $metadata->table['indexes'][0]['columns'];

        $nameField  = $metadata->fieldMappings['name'];
        $valueField = $metadata->fieldMappings['value'];

        $this->assertEquals('name', $unique[0]);
        $this->assertEquals('value', $unique[1]);

        $this->assertEquals('value', $indexes[0]);
        $this->assertEquals('name', $indexes[1]);

        $this->assertEquals(255, $nameField['length']);
        $this->assertEquals(255, $valueField['length']);
    }

    public function testEmbeddableAttributeOverride()
    {
        $metadata = $this->createClassMetadata('Doctrine\Tests\Models\Overrides\EntityWithEmbeddableOverriddenAttribute');

        $this->assertArrayHasKey('valueObject', $metadata->embeddedClasses);
        $this->assertArrayHasKey('attributeOverrides', $metadata->embeddedClasses['valueObject']);
        $this->assertNotNull($metadata->embeddedClasses['valueObject']['attributeOverrides']);
        $this->assertArrayHasKey('value', $metadata->embeddedClasses['valueObject']['attributeOverrides']);
        $this->assertEquals($metadata->embeddedClasses['valueObject']['attributeOverrides']['value']['columnName'], 'value_override');
    }

    public function testEmbeddableAttributeRemoval()
    {
        $metadata = $this->createClassMetadata('Doctrine\Tests\Models\Overrides\EntityWithEmbeddableRemovedAttribute');

        $this->assertArrayHasKey('valueObject', $metadata->embeddedClasses);
        $this->assertArrayHasKey('attributeOverrides', $metadata->embeddedClasses['valueObject']);
        $this->assertNotNull($metadata->embeddedClasses['valueObject']['attributeOverrides']);
        $this->assertArrayHasKey('value', $metadata->embeddedClasses['valueObject']['attributeOverrides']);
        $this->assertNull($metadata->embeddedClasses['valueObject']['attributeOverrides']['value']);
    }

    public function testEmbeddableAttributeOverrides()
    {
        $metadata = $this->createClassMetadata('Doctrine\Tests\Models\Overrides\EntityWithEmbeddableOverriddenAndRemovedAttributes');

        $this->assertArrayHasKey('valueObject', $metadata->embeddedClasses);
        $this->assertArrayHasKey('attributeOverrides', $metadata->embeddedClasses['valueObject']);
        $this->assertNotNull($metadata->embeddedClasses['valueObject']['attributeOverrides']);
        $this->assertArrayHasKey('value', $metadata->embeddedClasses['valueObject']['attributeOverrides']);
        $this->assertTrue(is_array($metadata->embeddedClasses['valueObject']['attributeOverrides']['value']));
        $this->assertEquals($metadata->embeddedClasses['valueObject']['attributeOverrides']['value']['columnName'], 'value_override');
        $this->assertArrayHasKey('count', $metadata->embeddedClasses['valueObject']['attributeOverrides']);
        $this->assertNull($metadata->embeddedClasses['valueObject']['attributeOverrides']['count']);
    }

    public function testNestedEmbeddableAttributeOverrides()
    {
        $metadata = $this->createClassMetadata('Doctrine\Tests\Models\Overrides\EntityWithNestedEmbeddableOverriddenAndRemovedAttributes');

        $this->assertArrayHasKey('nestedValueObject', $metadata->embeddedClasses);
        $this->assertArrayHasKey('attributeOverrides', $metadata->embeddedClasses['nestedValueObject']);
        $this->assertNotNull($metadata->embeddedClasses['nestedValueObject']['attributeOverrides']);
        $this->assertArrayHasKey('nested.value', $metadata->embeddedClasses['nestedValueObject']['attributeOverrides']);
        $this->assertTrue(is_array($metadata->embeddedClasses['nestedValueObject']['attributeOverrides']['nested.value']));
        $this->assertEquals($metadata->embeddedClasses['nestedValueObject']['attributeOverrides']['nested.value']['columnName'], 'value_override');
        $this->assertArrayHasKey('nested.count', $metadata->embeddedClasses['nestedValueObject']['attributeOverrides']);
        $this->assertNull($metadata->embeddedClasses['nestedValueObject']['attributeOverrides']['nested.count']);
    }

}

class DDC2069Entity
{
    public $id;

    public $name;

    public $value;
}
