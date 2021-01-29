<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\ConvertDoctrine1Schema;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\VerifyDeprecations;

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
    use VerifyDeprecations;

    protected function _createEntityManager($metadataDriver)
    {
        $driverMock = new DriverMock();
        $config     = new Configuration();
        $config->setProxyDir(__DIR__ . '/../../Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $eventManager = new EventManager();
        $conn         = new ConnectionMock([], $driverMock, $config, $eventManager);
        $config->setMetadataDriverImpl($metadataDriver);

        return EntityManagerMock::create($conn, $config, $eventManager);
    }

    public function testTest(): void
    {
        if (! class_exists('Symfony\Component\Yaml\Yaml', true)) {
            $this->markTestSkipped('Please install Symfony YAML Component into the include path of your PHP installation.');
        }

        $cme       = new ClassMetadataExporter();
        $converter = new ConvertDoctrine1Schema(__DIR__ . '/doctrine1schema');

        $exporter = $cme->getExporter('yml', __DIR__ . '/convert');
        $exporter->setOverwriteExistingFiles(true);
        $exporter->setMetadata($converter->getMetadata());
        $exporter->export();

        $this->assertTrue(file_exists(__DIR__ . '/convert/User.dcm.yml'));
        $this->assertTrue(file_exists(__DIR__ . '/convert/Profile.dcm.yml'));

        $metadataDriver = new YamlDriver(__DIR__ . '/convert');
        $em             = $this->_createEntityManager($metadataDriver);
        $cmf            = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        $metadata     = $cmf->getAllMetadata();
        $profileClass = $cmf->getMetadataFor('Profile');
        $userClass    = $cmf->getMetadataFor('User');

        $this->assertEquals(2, count($metadata));
        $this->assertEquals('Profile', $profileClass->name);
        $this->assertEquals('User', $userClass->name);
        $this->assertEquals(4, count($profileClass->fieldMappings));
        $this->assertEquals(5, count($userClass->fieldMappings));
        $this->assertEquals('text', $userClass->fieldMappings['clob']['type']);
        $this->assertEquals('test_alias', $userClass->fieldMappings['theAlias']['columnName']);
        $this->assertEquals('theAlias', $userClass->fieldMappings['theAlias']['fieldName']);

        $this->assertEquals('Profile', $profileClass->associationMappings['User']['sourceEntity']);
        $this->assertEquals('User', $profileClass->associationMappings['User']['targetEntity']);

        $this->assertEquals('username', $userClass->table['uniqueConstraints']['username']['columns'][0]);
        $this->assertHasDeprecationMessages();
    }

    public function tearDown(): void
    {
        @unlink(__DIR__ . '/convert/User.dcm.yml');
        @unlink(__DIR__ . '/convert/Profile.dcm.yml');
        @rmdir(__DIR__ . '/convert');
    }
}
