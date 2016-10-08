<?php

namespace Doctrine\Tests\ORM\Tools\Export;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\OrmTestCase;

/**
 * Test case for ClassMetadataExporter
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
abstract class AbstractClassMetadataExporterTest extends OrmTestCase
{
    protected $_extension;

    abstract protected function _getType();

    protected function _createEntityManager($metadataDriver)
    {
        $driverMock = new DriverMock();
        $config     = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $eventManager = new EventManager();
        $conn = new ConnectionMock([], $driverMock, $config, $eventManager);
        $config->setMetadataDriverImpl($metadataDriver);

        $driverMock   = new DriverMock();
        $eventManager = new EventManager();
        $connection   = new ConnectionMock([], $driverMock, $config, $eventManager);

        return EntityManagerMock::create($connection, $config, $eventManager);
    }

    protected function _createMetadataDriver($type, $path)
    {
        $mappingDriver = [
            'php'        => PHPDriver::class,
            'annotation' => AnnotationDriver::class,
            'xml'        => XmlDriver::class,
        ];

        self::assertArrayHasKey($type, $mappingDriver, "There is no metadata driver for the type '" . $type . "'.");

        $class  = $mappingDriver[$type];
        $driver = ($type === 'annotation')
            ? $this->createAnnotationDriver([$path])
            : new $class($path);

        return $driver;
    }

    protected function _createClassMetadataFactory($em, $type)
    {
        $factory = ($type === 'annotation')
            ? new ClassMetadataFactory()
            : new DisconnectedClassMetadataFactory();

        $factory->setEntityManager($em);

        return $factory;
    }

    public function testExportDirectoryAndFilesAreCreated()
    {
        $this->_deleteDirectory(__DIR__ . '/export/'.$this->_getType());

        $type = $this->_getType();
        $metadataDriver = $this->_createMetadataDriver($type, __DIR__ . '/' . $type);
        $em = $this->_createEntityManager($metadataDriver);
        $cmf = $this->_createClassMetadataFactory($em, $type);
        $metadata = $cmf->getAllMetadata();

        $metadata[0]->name = ExportedUser::class;

        self::assertEquals(ExportedUser::class, $metadata[0]->name);

        $type = $this->_getType();
        $cme = new ClassMetadataExporter();
        $exporter = $cme->getExporter($type, __DIR__ . '/export/' . $type);

        if ($type === 'annotation') {
            $entityGenerator = new EntityGenerator();

            $entityGenerator->setAnnotationPrefix("");
            $exporter->setEntityGenerator($entityGenerator);
        }

        $this->_extension = $exporter->getExtension();

        $exporter->setMetadata($metadata);
        $exporter->export();

        if ($type == 'annotation') {
            self::assertTrue(file_exists(__DIR__ . '/export/' . $type . '/'.str_replace('\\', '/', ExportedUser::class).$this->_extension));
        } else {
            self::assertTrue(file_exists(__DIR__ . '/export/' . $type . '/Doctrine.Tests.ORM.Tools.Export.ExportedUser'.$this->_extension));
        }
    }

    /**
     * @depends testExportDirectoryAndFilesAreCreated
     */
    public function testExportedMetadataCanBeReadBackIn()
    {
        $type = $this->_getType();

        $metadataDriver = $this->_createMetadataDriver($type, __DIR__ . '/export/' . $type);
        $em = $this->_createEntityManager($metadataDriver);
        $cmf = $this->_createClassMetadataFactory($em, $type);
        $metadata = $cmf->getAllMetadata();

        self::assertEquals(1, count($metadata));

        $class = current($metadata);

        self::assertEquals(ExportedUser::class, $class->name);

        return $class;
    }

    /**
     * @depends testExportedMetadataCanBeReadBackIn
     * @param ClassMetadata $class
     */
    public function testTableIsExported($class)
    {
        self::assertEquals('cms_users', $class->table->getName());
        self::assertEquals(
            ['engine' => 'MyISAM', 'foo' => ['bar' => 'baz']],
            $class->table->getOptions()
        );

        return $class;
    }

    /**
     * @depends testTableIsExported
     * @param ClassMetadata $class
     */
    public function testTypeIsExported($class)
    {
        self::assertFalse($class->isMappedSuperclass);

        return $class;
    }

    /**
     * @depends testTypeIsExported
     * @param ClassMetadata $class
     */
    public function testIdentifierIsExported($class)
    {
        self::assertNotNull($class->getProperty('id'));

        $property = $class->getProperty('id');

        self::assertTrue($property->isPrimaryKey());
        self::assertEquals(['id'], $class->identifier);
        self::assertEquals(ClassMetadata::GENERATOR_TYPE_IDENTITY, $class->generatorType, "Generator Type wrong");

        return $class;
    }

    /**
     * @depends testIdentifierIsExported
     * @param ClassMetadata $class
     */
    public function testFieldsAreExported($class)
    {
        self::assertNotNull($class->getProperty('id'));
        self::assertNotNull($class->getProperty('name'));
        self::assertNotNull($class->getProperty('email'));
        self::assertNotNull($class->getProperty('age'));

        $idProperty = $class->getProperty('id');
        $nameProperty = $class->getProperty('name');
        $emailProperty = $class->getProperty('email');
        $ageProperty = $class->getProperty('age');

        self::assertTrue($idProperty->isPrimaryKey());
        self::assertEquals('id', $idProperty->getName());
        self::assertEquals('integer', $idProperty->getTypeName());
        self::assertEquals('id', $idProperty->getColumnName());

        self::assertEquals('name', $nameProperty->getName());
        self::assertEquals('string', $nameProperty->getTypeName());
        self::assertEquals('name', $nameProperty->getColumnName());
        self::assertEquals(50, $nameProperty->getLength());

        self::assertEquals('email', $emailProperty->getName());
        self::assertEquals('string', $emailProperty->getTypeName());
        self::assertEquals('user_email', $emailProperty->getColumnName());
        self::assertEquals('CHAR(32) NOT NULL', $emailProperty->getColumnDefinition());

        self::assertEquals('age', $ageProperty->getName());
        self::assertEquals('integer', $ageProperty->getTypeName());
        self::assertEquals('age', $ageProperty->getColumnName());
        self::assertArrayHasKey('unsigned', $ageProperty->getOptions());
        self::assertEquals(true, $ageProperty->getOptions()['unsigned']);

        return $class;
    }

    /**
     * @depends testFieldsAreExported
     * @param ClassMetadata $class
     */
    public function testOneToOneAssociationsAreExported($class)
    {
        self::assertTrue(isset($class->associationMappings['address']));

        $association = $class->associationMappings['address'];
        $joinColumn  = reset($association['joinColumns']);

        self::assertEquals('Doctrine\Tests\ORM\Tools\Export\Address', $association['targetEntity']);
        self::assertEquals('address_id', $joinColumn->getColumnName());
        self::assertEquals('id', $joinColumn->getReferencedColumnName());
        self::assertEquals('CASCADE', $joinColumn->getOnDelete());

        self::assertContains('remove', $association['cascade']);
        self::assertContains('persist', $association['cascade']);
        self::assertNotContains('refresh', $association['cascade']);
        self::assertNotContains('merge', $association['cascade']);
        self::assertNotContains('detach', $association['cascade']);
        self::assertTrue($association['orphanRemoval']);
        self::assertEquals(ClassMetadata::FETCH_EAGER, $association['fetch']);

        return $class;
    }

    /**
     * @depends testFieldsAreExported
     */
    public function testManyToOneAssociationsAreExported($class)
    {
        self::assertTrue(isset($class->associationMappings['mainGroup']));
        self::assertEquals(Group::class, $class->associationMappings['mainGroup']['targetEntity']);
    }

    /**
     * @depends testOneToOneAssociationsAreExported
     * @param ClassMetadata $class
     */
    public function testOneToManyAssociationsAreExported($class)
    {
        self::assertTrue(isset($class->associationMappings['phonenumbers']));
        //self::assertInstanceOf('Doctrine\ORM\Mapping\OneToManyMapping', $class->associationMappings['phonenumbers']);
        self::assertEquals(Phonenumber::class, $class->associationMappings['phonenumbers']['targetEntity']);
        self::assertEquals('user', $class->associationMappings['phonenumbers']['mappedBy']);
        self::assertEquals(['number' => 'ASC'], $class->associationMappings['phonenumbers']['orderBy']);

        self::assertContains('remove', $class->associationMappings['phonenumbers']['cascade']);
        self::assertContains('persist', $class->associationMappings['phonenumbers']['cascade']);
        self::assertNotContains('refresh', $class->associationMappings['phonenumbers']['cascade']);
        self::assertContains('merge', $class->associationMappings['phonenumbers']['cascade']);
        self::assertNotContains('detach', $class->associationMappings['phonenumbers']['cascade']);
        self::assertTrue($class->associationMappings['phonenumbers']['orphanRemoval']);
        self::assertEquals(ClassMetadata::FETCH_LAZY, $class->associationMappings['phonenumbers']['fetch']);

        return $class;
    }

    /**
     * @depends testOneToManyAssociationsAreExported
     * @param ClassMetadata $metadata
     */
    public function testManyToManyAssociationsAreExported($class)
    {
        self::assertTrue(isset($class->associationMappings['groups']));

        $association        = $class->associationMappings['groups'];
        $joinColumns        = $association['joinTable']->getJoinColumns();
        $joinColumn         = reset($joinColumns);
        $inverseJoinColumns = $association['joinTable']->getInverseJoinColumns();
        $inverseJoinColumn  = reset($inverseJoinColumns);

        //self::assertInstanceOf('Doctrine\ORM\Mapping\ManyToManyMapping', $class->associationMappings['groups']);
        self::assertEquals(Group::class, $association['targetEntity']);
        self::assertEquals('cms_users_groups', $association['joinTable']->getName());

        self::assertEquals('user_id', $joinColumn->getColumnName());
        self::assertEquals('id', $joinColumn->getReferencedColumnName());

        self::assertEquals('group_id', $inverseJoinColumn->getColumnName());
        self::assertEquals('id', $inverseJoinColumn->getReferencedColumnName());
        self::assertEquals('INT NULL', $inverseJoinColumn->getColumnDefinition());

        self::assertContains('remove', $association['cascade']);
        self::assertContains('persist', $association['cascade']);
        self::assertContains('refresh', $association['cascade']);
        self::assertContains('merge', $association['cascade']);
        self::assertContains('detach', $association['cascade']);

        self::assertEquals(ClassMetadata::FETCH_EXTRA_LAZY, $association['fetch']);

        return $class;
    }

    /**
     * @depends testManyToManyAssociationsAreExported
     * @param ClassMetadata $class
     */
    public function testLifecycleCallbacksAreExported($class)
    {
        self::assertTrue(isset($class->lifecycleCallbacks['prePersist']));
        self::assertEquals(2, count($class->lifecycleCallbacks['prePersist']));
        self::assertEquals('doStuffOnPrePersist', $class->lifecycleCallbacks['prePersist'][0]);
        self::assertEquals('doOtherStuffOnPrePersistToo', $class->lifecycleCallbacks['prePersist'][1]);

        self::assertTrue(isset($class->lifecycleCallbacks['postPersist']));
        self::assertEquals(1, count($class->lifecycleCallbacks['postPersist']));
        self::assertEquals('doStuffOnPostPersist', $class->lifecycleCallbacks['postPersist'][0]);

        return $class;
    }

    /**
     * @depends testLifecycleCallbacksAreExported
     * @param ClassMetadata $class
     */
    public function testCascadeIsExported($class)
    {
        self::assertContains('persist', $class->associationMappings['phonenumbers']['cascade']);
        self::assertContains('merge', $class->associationMappings['phonenumbers']['cascade']);
        self::assertContains('remove', $class->associationMappings['phonenumbers']['cascade']);
        self::assertNotContains('refresh', $class->associationMappings['phonenumbers']['cascade']);
        self::assertNotContains('detach', $class->associationMappings['phonenumbers']['cascade']);
        self::assertTrue($class->associationMappings['phonenumbers']['orphanRemoval']);

        return $class;
    }

    /**
     * @depends testCascadeIsExported
     * @param ClassMetadata $class
     */
    public function testInversedByIsExported($class)
    {
        self::assertEquals('user', $class->associationMappings['address']['inversedBy']);
    }
	/**
     * @depends testExportDirectoryAndFilesAreCreated
     */
    public function testCascadeAllCollapsed()
    {
        $type = $this->_getType();

        if ($type == 'xml') {
            $xml = simplexml_load_file(__DIR__ . '/export/'.$type.'/Doctrine.Tests.ORM.Tools.Export.ExportedUser.dcm.xml');

            $xml->registerXPathNamespace("d", "http://doctrine-project.org/schemas/orm/doctrine-mapping");
            $nodes = $xml->xpath("/d:doctrine-mapping/d:entity/d:one-to-many[@field='interests']/d:cascade/d:*");
            self::assertEquals(1, count($nodes));

            self::assertEquals('cascade-all', $nodes[0]->getName());
        } else {
            $this->markTestSkipped('Test not available for '.$type.' driver');
        }
    }

    public function __destruct()
    {
#        $this->_deleteDirectory(__DIR__ . '/export/'.$this->_getType());
    }

    protected function _deleteDirectory($path)
    {
        if (is_file($path)) {
            return unlink($path);
        } else if (is_dir($path)) {
            $files = glob(rtrim($path,'/').'/*');

            if (is_array($files)) {
                foreach ($files as $file){
                    $this->_deleteDirectory($file);
                }
            }

            return rmdir($path);
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
