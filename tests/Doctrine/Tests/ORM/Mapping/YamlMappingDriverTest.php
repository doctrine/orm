<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

require_once __DIR__ . '/../../TestInit.php';

class YamlMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        if (!class_exists('Symfony\Component\Yaml\Yaml', true)) {
            $this->markTestSkipped('Please install Symfony YAML Component into the include path of your PHP installation.');
        }

        return new YamlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');
    }

    public function testConfigurationExtending()
    {
        $entityClassName = 'Doctrine\Tests\ORM\Mapping\Article';

        $extension = $this->getMock('Doctrine\ORM\Mapping\Driver\Configuration\YamlExtension');
        $extension
            ->expects($this->once())
            ->method('addConfiguration')
            ->with($this->isInstanceOf('Symfony\Component\Config\Definition\Builder\NodeDefinition'));

        $extension
            ->expects($this->once())
            ->method('addFieldConfiguration')
            ->with($this->isInstanceOf('Symfony\Component\Config\Definition\Builder\NodeDefinition'));

        $extension
            ->expects($this->once())
            ->method('addOneToOneConfiguration')
            ->with($this->isInstanceOf('Symfony\Component\Config\Definition\Builder\NodeDefinition'));

        $extension
            ->expects($this->once())
            ->method('addManyToOneConfiguration')
            ->with($this->isInstanceOf('Symfony\Component\Config\Definition\Builder\NodeDefinition'));

        $extension
            ->expects($this->once())
            ->method('addOneToManyConfiguration')
            ->with($this->isInstanceOf('Symfony\Component\Config\Definition\Builder\NodeDefinition'));

        $extension
            ->expects($this->once())
            ->method('addManyToManyConfiguration')
            ->with($this->isInstanceOf('Symfony\Component\Config\Definition\Builder\NodeDefinition'));

        $yamlDriver = $this->_loadDriver();
        $yamlDriver->addConfigurationExtension($extension);
        $class = new ClassMetadata($entityClassName);
        $class->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        $yamlDriver->loadMetadataForClass($entityClassName, $class);
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
        $factory = new \Doctrine\ORM\Mapping\ClassMetadataFactory();
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
}

class DDC2069Entity
{
    public $id;

    public $name;

    public $value;
}

class Article
{

}
