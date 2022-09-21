<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Tests\Models\DDC3711\DDC3711EntityA;
use Doctrine\Tests\Models\DirectoryTree\Directory;
use Doctrine\Tests\Models\DirectoryTree\File;
use Doctrine\Tests\Models\Generic\BooleanModel;
use Symfony\Component\Yaml\Yaml;

use function class_exists;

use const DIRECTORY_SEPARATOR;

class YamlMappingDriverTest extends MappingDriverTestCase
{
    protected function loadDriver(): MappingDriver
    {
        if (! class_exists(Yaml::class, true)) {
            self::markTestSkipped('Please install Symfony YAML Component into the include path of your PHP installation.');
        }

        return new YamlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');
    }

    /**
     * @group DDC-671
     *
     * Entities for this test are in AbstractMappingDriverTest
     */
    public function testJoinTablesWithMappedSuperclassForYamlDriver(): void
    {
        $yamlDriver = $this->loadDriver();
        $yamlDriver->getLocator()->addPaths([__DIR__ . DIRECTORY_SEPARATOR . 'yaml']);

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $classPage = new ClassMetadata(File::class);
        $classPage = $factory->getMetadataFor(File::class);
        self::assertEquals(File::class, $classPage->associationMappings['parentDirectory']['sourceEntity']);

        $classDirectory = new ClassMetadata(Directory::class);
        $classDirectory = $factory->getMetadataFor(Directory::class);
        self::assertEquals(Directory::class, $classDirectory->associationMappings['parentDirectory']['sourceEntity']);
    }

    /** @group DDC-1468 */
    public function testItMentionsFilenameAndEntityNameOnInvalidMapping(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Invalid mapping file \'Doctrine.Tests.Models.Generic.BooleanModel.dcm.yml\' for class \'Doctrine\Tests\Models\Generic\BooleanModel\'.');
        $this->createClassMetadata(BooleanModel::class);
    }

    /** @group DDC-2069 */
    public function testSpacesShouldBeIgnoredWhenUseExplode(): void
    {
        $metadata = $this->createClassMetadata(DDC2069Entity::class);
        $unique   = $metadata->table['uniqueConstraints'][0]['columns'];
        $indexes  = $metadata->table['indexes'][0]['columns'];

        $nameField  = $metadata->fieldMappings['name'];
        $valueField = $metadata->fieldMappings['value'];

        self::assertEquals('name', $unique[0]);
        self::assertEquals('value', $unique[1]);

        self::assertEquals('value', $indexes[0]);
        self::assertEquals('name', $indexes[1]);

        self::assertEquals(255, $nameField['length']);
        self::assertEquals(255, $valueField['length']);
    }

    public function testCompositeKeyForJoinTableInManyToManyCreation(): void
    {
        $yamlDriver = $this->loadDriver();

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($em);

        $entityA = new ClassMetadata(DDC3711EntityA::class);
        $entityA = $factory->getMetadataFor(DDC3711EntityA::class);

        self::assertEquals(['link_a_id1' => 'id1', 'link_a_id2' => 'id2'], $entityA->associationMappings['entityB']['relationToSourceKeyColumns']);
        self::assertEquals(['link_b_id1' => 'id1', 'link_b_id2' => 'id2'], $entityA->associationMappings['entityB']['relationToTargetKeyColumns']);
    }
}

class DDC2069Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var mixed */
    public $value;
}
