<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\ConvertDoctrine1Schema;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\TestUtil;

use function class_exists;
use function count;
use function file_exists;
use function rmdir;
use function unlink;

/**
 * Test case for converting a Doctrine 1 style schema to Doctrine 2 mapping files
 *
 * @link        http://www.phpdoctrine.org
 */
class ConvertDoctrine1SchemaTest extends OrmTestCase
{
    protected function createEntityManager(MappingDriver $metadataDriver): EntityManagerMock
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

    public function testTest(): void
    {
        if (! class_exists('Symfony\Component\Yaml\Yaml', true)) {
            self::markTestSkipped('Please install Symfony YAML Component into the include path of your PHP installation.');
        }

        $cme       = new ClassMetadataExporter();
        $converter = new ConvertDoctrine1Schema(__DIR__ . '/doctrine1schema');

        $exporter = $cme->getExporter('yml', __DIR__ . '/convert');
        $exporter->setOverwriteExistingFiles(true);
        $exporter->setMetadata($converter->getMetadata());
        $exporter->export();

        self::assertTrue(file_exists(__DIR__ . '/convert/User.dcm.yml'));
        self::assertTrue(file_exists(__DIR__ . '/convert/Profile.dcm.yml'));

        $metadataDriver = new YamlDriver(__DIR__ . '/convert');
        $em             = $this->createEntityManager($metadataDriver);
        $cmf            = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        $metadata     = $cmf->getAllMetadata();
        $profileClass = $cmf->getMetadataFor('Profile');
        $userClass    = $cmf->getMetadataFor('User');

        self::assertEquals(2, count($metadata));
        self::assertEquals('Profile', $profileClass->name);
        self::assertEquals('User', $userClass->name);
        self::assertEquals(4, count($profileClass->fieldMappings));
        self::assertEquals(5, count($userClass->fieldMappings));
        self::assertEquals('text', $userClass->fieldMappings['clob']['type']);
        self::assertEquals('test_alias', $userClass->fieldMappings['theAlias']['columnName']);
        self::assertEquals('theAlias', $userClass->fieldMappings['theAlias']['fieldName']);

        self::assertEquals('Profile', $profileClass->associationMappings['User']['sourceEntity']);
        self::assertEquals('User', $profileClass->associationMappings['User']['targetEntity']);

        self::assertEquals('username', $userClass->table['uniqueConstraints']['username']['columns'][0]);
    }

    public function tearDown(): void
    {
        @unlink(__DIR__ . '/convert/User.dcm.yml');
        @unlink(__DIR__ . '/convert/Profile.dcm.yml');
        @rmdir(__DIR__ . '/convert');
    }
}
