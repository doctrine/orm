<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Tests\Models\DirectoryTree\Directory;
use Doctrine\Tests\Models\DirectoryTree\File;
use Doctrine\Tests\Models\Generic\SerializationModel;
use Doctrine\Tests\VerifyDeprecations;
use Symfony\Component\Yaml\Yaml;

class YamlMappingDriverTest extends AbstractMappingDriverTest
{
    use VerifyDeprecations;

    protected function _loadDriver()
    {
        if (!class_exists(Yaml::class, true)) {
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
        $yamlDriver->getLocator()->addPaths([__DIR__ . DIRECTORY_SEPARATOR . 'yaml']);

        $em = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $classPage = new ClassMetadata(File::class);
        $classPage = $factory->getMetadataFor(File::class);
        $this->assertEquals(File::class, $classPage->associationMappings['parentDirectory']['sourceEntity']);

        $classDirectory = new ClassMetadata(Directory::class);
        $classDirectory = $factory->getMetadataFor(Directory::class);
        $this->assertEquals(Directory::class, $classDirectory->associationMappings['parentDirectory']['sourceEntity']);
        $this->assertHasDeprecationMessages();
    }

    /**
     * @group DDC-1468
     *
     * @expectedException Doctrine\Common\Persistence\Mapping\MappingException
     * @expectedExceptionMessage Invalid mapping file 'Doctrine.Tests.Models.Generic.SerializationModel.dcm.yml' for class 'Doctrine\Tests\Models\Generic\SerializationModel'.
     */
    public function testInvalidMappingFileException()
    {
        $this->createClassMetadata(SerializationModel::class);
    }

    /**
     * @group DDC-2069
     */
    public function testSpacesShouldBeIgnoredWhenUseExplode()
    {
        $metadata = $this->createClassMetadata(DDC2069Entity::class);
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
        $this->assertHasDeprecationMessages();
    }

    public function testDeprecation() : void
    {
        $this->createClassMetadata(DDC2069Entity::class);
        $this->expectDeprecationMessage('YAML mapping driver is deprecated and will be removed in Doctrine ORM 3.0, please migrate to annotation or XML driver.');
    }

}

class DDC2069Entity
{
    public $id;

    public $name;

    public $value;
}
