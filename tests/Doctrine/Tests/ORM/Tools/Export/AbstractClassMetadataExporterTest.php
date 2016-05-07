<?php

namespace Doctrine\Tests\ORM\Tools\Export;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\OrmTestCase;
use Symfony\Component\Yaml\Parser;

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
        $config = new Configuration();
        $config->setProxyDir(__DIR__ . '/../../Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $eventManager = new EventManager();
        $conn = new ConnectionMock([], $driverMock, $config, $eventManager);
        $config->setMetadataDriverImpl($metadataDriver);

        return EntityManagerMock::create($conn, $config, $eventManager);
    }

    protected function _createMetadataDriver($type, $path)
    {
        $mappingDriver = [
            'php'        => PHPDriver::class,
            'annotation' => AnnotationDriver::class,
            'xml'        => XmlDriver::class,
            'yaml'       => YamlDriver::class,
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
        self::assertEquals('cms_users', $class->table['name']);
        self::assertEquals(
            ['engine' => 'MyISAM', 'foo' => ['bar' => 'baz']],
            $class->table['options']);

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
        self::assertEquals(ClassMetadata::GENERATOR_TYPE_IDENTITY, $class->generatorType, "Generator Type wrong");
        self::assertEquals(['id'], $class->identifier);
        self::assertTrue(isset($class->fieldMappings['id']['id']) && $class->fieldMappings['id']['id'] === true);

        return $class;
    }

    /**
     * @depends testIdentifierIsExported
     * @param ClassMetadata $class
     */
    public function testFieldsAreExported($class)
    {
        self::assertTrue(isset($class->fieldMappings['id']['id']) && $class->fieldMappings['id']['id'] === true);
        self::assertEquals('id', $class->fieldMappings['id']['fieldName']);
        self::assertEquals('integer', $class->fieldMappings['id']['type']->getName());
        self::assertEquals('id', $class->fieldMappings['id']['columnName']);

        self::assertEquals('name', $class->fieldMappings['name']['fieldName']);
        self::assertEquals('string', $class->fieldMappings['name']['type']->getName());
        self::assertEquals(50, $class->fieldMappings['name']['length']);
        self::assertEquals('name', $class->fieldMappings['name']['columnName']);

        self::assertEquals('email', $class->fieldMappings['email']['fieldName']);
        self::assertEquals('string', $class->fieldMappings['email']['type']->getName());
        self::assertEquals('user_email', $class->fieldMappings['email']['columnName']);
        self::assertEquals('CHAR(32) NOT NULL', $class->fieldMappings['email']['columnDefinition']);

        self::assertEquals(true, $class->fieldMappings['age']['options']['unsigned']);

        return $class;
    }

    /**
     * @depends testExportDirectoryAndFilesAreCreated
     */
    public function testFieldsAreProperlySerialized()
    {
        $type = $this->_getType();

        if ($type == 'xml') {
            $xml = simplexml_load_file(__DIR__ . '/export/'.$type.'/Doctrine.Tests.ORM.Tools.Export.ExportedUser.dcm.xml');

            $xml->registerXPathNamespace("d", "http://doctrine-project.org/schemas/orm/doctrine-mapping");
            $nodes = $xml->xpath("/d:doctrine-mapping/d:entity/d:field[@name='name' and @type='string' and @nullable='true']");
            self::assertEquals(1, count($nodes));

            $nodes = $xml->xpath("/d:doctrine-mapping/d:entity/d:field[@name='name' and @type='string' and @unique='true']");
            self::assertEquals(1, count($nodes));
        } else {
            $this->markTestSkipped('Test not available for '.$type.' driver');
        }
    }

    /**
     * @depends testFieldsAreExported
     * @param ClassMetadata $class
     */
    public function testOneToOneAssociationsAreExported($class)
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

        self::assertTrue($class->associationMappings['phonenumbers']['isCascadeRemove']);
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadePersist']);
        self::assertFalse($class->associationMappings['phonenumbers']['isCascadeRefresh']);
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadeMerge']);
        self::assertFalse($class->associationMappings['phonenumbers']['isCascadeDetach']);
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
        //self::assertInstanceOf('Doctrine\ORM\Mapping\ManyToManyMapping', $class->associationMappings['groups']);
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
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadePersist']);
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadeMerge']);
        self::assertTrue($class->associationMappings['phonenumbers']['isCascadeRemove']);
        self::assertFalse($class->associationMappings['phonenumbers']['isCascadeRefresh']);
        self::assertFalse($class->associationMappings['phonenumbers']['isCascadeDetach']);
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
        } else if ($type == 'yaml') {
            $yaml = new Parser();
            $value = $yaml->parse(file_get_contents(__DIR__ . '/export/'.$type.'/Doctrine.Tests.ORM.Tools.Export.ExportedUser.dcm.yml'));

            self::assertTrue(isset($value[ExportedUser::class]['oneToMany']['interests']['cascade']));
            self::assertEquals(1, count($value[ExportedUser::class]['oneToMany']['interests']['cascade']));
            self::assertEquals('all', $value[ExportedUser::class]['oneToMany']['interests']['cascade'][0]);
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
