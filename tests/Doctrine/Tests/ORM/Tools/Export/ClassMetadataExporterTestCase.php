<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Export;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\TestUtil;
use Symfony\Component\Yaml\Parser;

use function count;
use function current;
use function file_get_contents;
use function glob;
use function is_array;
use function is_dir;
use function is_file;
use function rmdir;
use function rtrim;
use function simplexml_load_file;
use function str_replace;
use function unlink;

/**
 * Test case for ClassMetadataExporter
 *
 * @link        http://www.phpdoctrine.org
 */
abstract class ClassMetadataExporterTestCase extends OrmTestCase
{
    /** @var string|null */
    protected $extension;

    abstract protected function getType(): string;

    protected function createEntityManager($metadataDriver): EntityManagerMock
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('supportsIdentityColumns')
            ->willReturn(true);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);
        $connection->method('getEventManager')
            ->willReturn(new EventManager());

        $config = new Configuration();
        TestUtil::configureProxies($config);
        $config->setMetadataDriverImpl($metadataDriver);

        return new EntityManagerMock($connection, $config);
    }

    protected function createMetadataDriver(string $type, string $path): MappingDriver
    {
        $mappingDriver = [
            'php'        => PHPDriver::class,
            'annotation' => AnnotationDriver::class,
            'xml'        => XmlDriver::class,
            'yaml'       => YamlDriver::class,
        ];

        self::assertArrayHasKey($type, $mappingDriver, "There is no metadata driver for the type '" . $type . "'.");

        $class = $mappingDriver[$type];

        return $type === 'annotation'
            ? $this->createAnnotationDriver([$path])
            : new $class($path);
    }

    protected function createClassMetadataFactory(EntityManagerInterface $em, string $type): ClassMetadataFactory
    {
        $factory = $type === 'annotation'
            ? new ClassMetadataFactory()
            : new DisconnectedClassMetadataFactory();

        $factory->setEntityManager($em);

        return $factory;
    }

    public function testExportDirectoryAndFilesAreCreated(): void
    {
        $this->deleteDirectory(__DIR__ . '/export/' . $this->getType());

        $type           = $this->getType();
        $metadataDriver = $this->createMetadataDriver($type, __DIR__ . '/' . $type);
        $em             = $this->createEntityManager($metadataDriver);
        $cmf            = $this->createClassMetadataFactory($em, $type);
        $metadata       = $cmf->getAllMetadata();

        $metadata[0]->name = ExportedUser::class;

        self::assertEquals(ExportedUser::class, $metadata[0]->name);

        $type     = $this->getType();
        $cme      = new ClassMetadataExporter();
        $exporter = $cme->getExporter($type, __DIR__ . '/export/' . $type);

        if ($type === 'annotation') {
            $entityGenerator = new EntityGenerator();

            $exporter->setEntityGenerator($entityGenerator);
        }

        $this->extension = $exporter->getExtension();

        $exporter->setMetadata($metadata);
        $exporter->export();

        if ($type === 'annotation') {
            self::assertFileExists(__DIR__ . '/export/' . $type . '/' . str_replace('\\', '/', ExportedUser::class) . $this->extension);
        } else {
            self::assertFileExists(__DIR__ . '/export/' . $type . '/Doctrine.Tests.ORM.Tools.Export.ExportedUser' . $this->extension);
        }
    }

    /** @depends testExportDirectoryAndFilesAreCreated */
    public function testExportedMetadataCanBeReadBackIn(): ClassMetadata
    {
        $type = $this->getType();

        $metadataDriver = $this->createMetadataDriver($type, __DIR__ . '/export/' . $type);
        $em             = $this->createEntityManager($metadataDriver);
        $cmf            = $this->createClassMetadataFactory($em, $type);
        $metadata       = $cmf->getAllMetadata();

        self::assertCount(1, $metadata);

        $class = current($metadata);

        self::assertEquals(ExportedUser::class, $class->name);

        return $class;
    }

    /** @depends testExportedMetadataCanBeReadBackIn */
    public function testTableIsExported(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals('cms_users', $class->table['name']);
        self::assertEquals(
            ['engine' => 'MyISAM', 'foo' => ['bar' => 'baz']],
            $class->table['options']
        );

        return $class;
    }

    /** @depends testTableIsExported */
    public function testTypeIsExported(ClassMetadata $class): ClassMetadata
    {
        self::assertFalse($class->isMappedSuperclass);

        return $class;
    }

    /** @depends testTypeIsExported */
    public function testIdentifierIsExported(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals(ClassMetadata::GENERATOR_TYPE_IDENTITY, $class->generatorType, 'Generator Type wrong');
        self::assertEquals(['id'], $class->identifier);
        self::assertTrue(isset($class->fieldMappings['id']['id']) && $class->fieldMappings['id']['id'] === true);

        return $class;
    }

    /** @depends testIdentifierIsExported */
    public function testFieldsAreExported(ClassMetadata $class): ClassMetadata
    {
        self::assertTrue(isset($class->fieldMappings['id']['id']) && $class->fieldMappings['id']['id'] === true);
        self::assertEquals('id', $class->fieldMappings['id']['fieldName']);
        self::assertEquals('integer', $class->fieldMappings['id']['type']);
        self::assertEquals('id', $class->fieldMappings['id']['columnName']);

        self::assertEquals('name', $class->fieldMappings['name']['fieldName']);
        self::assertEquals('string', $class->fieldMappings['name']['type']);
        self::assertEquals(50, $class->fieldMappings['name']['length']);
        self::assertEquals('name', $class->fieldMappings['name']['columnName']);

        self::assertEquals('email', $class->fieldMappings['email']['fieldName']);
        self::assertEquals('string', $class->fieldMappings['email']['type']);
        self::assertEquals('user_email', $class->fieldMappings['email']['columnName']);
        self::assertEquals('CHAR(32) NOT NULL', $class->fieldMappings['email']['columnDefinition']);

        self::assertTrue($class->fieldMappings['age']['options']['unsigned']);

        return $class;
    }

    /** @depends testExportDirectoryAndFilesAreCreated */
    public function testFieldsAreProperlySerialized(): void
    {
        $type = $this->getType();

        if ($type === 'xml') {
            $xml = simplexml_load_file(__DIR__ . '/export/' . $type . '/Doctrine.Tests.ORM.Tools.Export.ExportedUser.dcm.xml');

            $xml->registerXPathNamespace('d', 'http://doctrine-project.org/schemas/orm/doctrine-mapping');
            $nodes = $xml->xpath("/d:doctrine-mapping/d:entity/d:field[@name='name' and @type='string' and @nullable='true']");
            self::assertEquals(1, count($nodes));

            $nodes = $xml->xpath("/d:doctrine-mapping/d:entity/d:field[@name='name' and @type='string' and @unique='true']");
            self::assertEquals(1, count($nodes));
        } else {
            self::markTestSkipped('Test not available for ' . $type . ' driver');
        }
    }

    /** @depends testFieldsAreExported */
    public function testOneToOneAssociationsAreExported(ClassMetadata $class): ClassMetadata
    {
        self::assertTrue(isset($class->associationMappings['address']));
        self::assertEquals(Address::class, $class->associationMappings['address']['targetEntity']);
        self::assertEquals('address_id', $class->associationMappings['address']['joinColumns'][0]['name']);
        self::assertEquals('id', $class->associationMappings['address']['joinColumns'][0]['referencedColumnName']);
        self::assertEquals('CASCADE', $class->associationMappings['address']['joinColumns'][0]['onDelete']);

        self::assertTrue($class->associationMappings['address']['isCascadeRemove']);
        self::assertTrue($class->associationMappings['address']['isCascadePersist']);
        self::assertFalse($class->associationMappings['address']['isCascadeRefresh']);
        self::assertFalse($class->associationMappings['address']['isCascadeMerge']);
        self::assertFalse($class->associationMappings['address']['isCascadeDetach']);
        self::assertTrue($class->associationMappings['address']['orphanRemoval']);
        self::assertEquals(ClassMetadata::FETCH_EAGER, $class->associationMappings['address']['fetch']);

        return $class;
    }

    /** @depends testFieldsAreExported */
    public function testManyToOneAssociationsAreExported($class): void
    {
        self::assertTrue(isset($class->associationMappings['mainGroup']));
        self::assertEquals(Group::class, $class->associationMappings['mainGroup']['targetEntity']);
    }

    /** @depends testOneToOneAssociationsAreExported */
    public function testOneToManyAssociationsAreExported(ClassMetadata $class): ClassMetadata
    {
        self::assertTrue(isset($class->associationMappings['phonenumbers']));
        self::assertEquals(Phonenumber::class, $class->associationMappings['phonenumbers']['targetEntity']);
        self::assertEquals('user', $class->associationMappings['phonenumbers']['mappedBy']);
        self::assertEquals(['number' => 'ASC'], $class->associationMappings['phonenumbers']['orderBy']);

        self::assertTrue($class->associationMappings['phonenumbers']['isCascadeRemove']);
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadePersist']);
        self::assertFalse($class->associationMappings['phonenumbers']['isCascadeRefresh']);
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadeMerge']);
        self::assertFalse($class->associationMappings['phonenumbers']['isCascadeDetach']);
        self::assertTrue($class->associationMappings['phonenumbers']['orphanRemoval']);
        self::assertEquals(ClassMetadata::FETCH_LAZY, $class->associationMappings['phonenumbers']['fetch']);

        return $class;
    }

    /** @depends testOneToManyAssociationsAreExported */
    public function testManyToManyAssociationsAreExported(ClassMetadata $class): ClassMetadata
    {
        self::assertTrue(isset($class->associationMappings['groups']));
        self::assertEquals(Group::class, $class->associationMappings['groups']['targetEntity']);
        self::assertEquals('cms_users_groups', $class->associationMappings['groups']['joinTable']['name']);

        self::assertEquals('user_id', $class->associationMappings['groups']['joinTable']['joinColumns'][0]['name']);
        self::assertEquals('id', $class->associationMappings['groups']['joinTable']['joinColumns'][0]['referencedColumnName']);

        self::assertEquals('group_id', $class->associationMappings['groups']['joinTable']['inverseJoinColumns'][0]['name']);
        self::assertEquals('id', $class->associationMappings['groups']['joinTable']['inverseJoinColumns'][0]['referencedColumnName']);
        self::assertEquals('INT NULL', $class->associationMappings['groups']['joinTable']['inverseJoinColumns'][0]['columnDefinition']);

        self::assertTrue($class->associationMappings['groups']['isCascadeRemove']);
        self::assertTrue($class->associationMappings['groups']['isCascadePersist']);
        self::assertTrue($class->associationMappings['groups']['isCascadeRefresh']);
        self::assertTrue($class->associationMappings['groups']['isCascadeMerge']);
        self::assertTrue($class->associationMappings['groups']['isCascadeDetach']);
        self::assertEquals(ClassMetadata::FETCH_EXTRA_LAZY, $class->associationMappings['groups']['fetch']);

        return $class;
    }

    /** @depends testManyToManyAssociationsAreExported */
    public function testLifecycleCallbacksAreExported(ClassMetadata $class): ClassMetadata
    {
        self::assertTrue(isset($class->lifecycleCallbacks['prePersist']));
        self::assertCount(2, $class->lifecycleCallbacks['prePersist']);
        self::assertEquals('doStuffOnPrePersist', $class->lifecycleCallbacks['prePersist'][0]);
        self::assertEquals('doOtherStuffOnPrePersistToo', $class->lifecycleCallbacks['prePersist'][1]);

        self::assertTrue(isset($class->lifecycleCallbacks['postPersist']));
        self::assertCount(1, $class->lifecycleCallbacks['postPersist']);
        self::assertEquals('doStuffOnPostPersist', $class->lifecycleCallbacks['postPersist'][0]);

        return $class;
    }

    /** @depends testLifecycleCallbacksAreExported */
    public function testCascadeIsExported(ClassMetadata $class): ClassMetadata
    {
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadePersist']);
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadeMerge']);
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadeRemove']);
        self::assertFalse($class->associationMappings['phonenumbers']['isCascadeRefresh']);
        self::assertFalse($class->associationMappings['phonenumbers']['isCascadeDetach']);
        self::assertTrue($class->associationMappings['phonenumbers']['orphanRemoval']);

        return $class;
    }

    /** @depends testCascadeIsExported */
    public function testInversedByIsExported(ClassMetadata $class): void
    {
        self::assertEquals('user', $class->associationMappings['address']['inversedBy']);
    }

    /** @depends testExportDirectoryAndFilesAreCreated */
    public function testCascadeAllCollapsed(): void
    {
        $type = $this->getType();

        if ($type === 'xml') {
            $xml = simplexml_load_file(__DIR__ . '/export/' . $type . '/Doctrine.Tests.ORM.Tools.Export.ExportedUser.dcm.xml');

            $xml->registerXPathNamespace('d', 'http://doctrine-project.org/schemas/orm/doctrine-mapping');
            $nodes = $xml->xpath("/d:doctrine-mapping/d:entity/d:one-to-many[@field='interests']/d:cascade/d:*");
            self::assertEquals(1, count($nodes));

            self::assertEquals('cascade-all', $nodes[0]->getName());
        } elseif ($type === 'yaml') {
            $yaml  = new Parser();
            $value = $yaml->parse(file_get_contents(__DIR__ . '/export/' . $type . '/Doctrine.Tests.ORM.Tools.Export.ExportedUser.dcm.yml'));

            self::assertTrue(isset($value[ExportedUser::class]['oneToMany']['interests']['cascade']));
            self::assertEquals(1, count($value[ExportedUser::class]['oneToMany']['interests']['cascade']));
            self::assertEquals('all', $value[ExportedUser::class]['oneToMany']['interests']['cascade'][0]);
        } else {
            self::markTestSkipped('Test not available for ' . $type . ' driver');
        }
    }

    /** @depends testExportedMetadataCanBeReadBackIn */
    public function testEntityListenersAreExported(ClassMetadata $class): void
    {
        self::assertNotEmpty($class->entityListeners);
        self::assertCount(2, $class->entityListeners[Events::prePersist]);
        self::assertCount(2, $class->entityListeners[Events::postPersist]);
        self::assertEquals(UserListener::class, $class->entityListeners[Events::prePersist][0]['class']);
        self::assertEquals('customPrePersist', $class->entityListeners[Events::prePersist][0]['method']);
        self::assertEquals(GroupListener::class, $class->entityListeners[Events::prePersist][1]['class']);
        self::assertEquals('prePersist', $class->entityListeners[Events::prePersist][1]['method']);
        self::assertEquals(UserListener::class, $class->entityListeners[Events::postPersist][0]['class']);
        self::assertEquals('customPostPersist', $class->entityListeners[Events::postPersist][0]['method']);
        self::assertEquals(AddressListener::class, $class->entityListeners[Events::postPersist][1]['class']);
        self::assertEquals('customPostPersist', $class->entityListeners[Events::postPersist][1]['method']);
    }

    protected function deleteDirectory(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        } elseif (is_dir($path)) {
            $files = glob(rtrim($path, '/') . '/*');

            if (is_array($files)) {
                foreach ($files as $file) {
                    $this->deleteDirectory($file);
                }
            }

            rmdir($path);
        }
    }
}

class Address
{
}
class Phonenumber
{
}
class Group
{
}
class UserListener
{
    /** @\Doctrine\ORM\Mapping\PrePersist */
    public function customPrePersist(): void
    {
    }

    /** @\Doctrine\ORM\Mapping\PostPersist */
    public function customPostPersist(): void
    {
    }
}
class GroupListener
{
    /** @\Doctrine\ORM\Mapping\PrePersist */
    public function prePersist(): void
    {
    }
}
class AddressListener
{
    /** @\Doctrine\ORM\Mapping\PostPersist */
    public function customPostPersist(): void
    {
    }
}
