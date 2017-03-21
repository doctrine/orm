<?php

namespace Doctrine\Tests\ORM\Tools\Export;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\Mapping\GeneratorType;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
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
    protected $extension;

    abstract protected function getType();

    protected function createEntityManager($metadataDriver)
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

    protected function createMetadataDriver($type, $path)
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

    protected function createClassMetadataFactory($em, $type)
    {
        $factory = ($type === 'annotation')
            ? new ClassMetadataFactory()
            : new DisconnectedClassMetadataFactory();

        $factory->setEntityManager($em);

        return $factory;
    }

    public function testExportDirectoryAndFilesAreCreated()
    {
        $this->deleteDirectory(__DIR__ . '/export/'.$this->getType());

        $type = $this->getType();
        $metadataDriver = $this->createMetadataDriver($type, __DIR__ . '/' . $type);
        $em = $this->createEntityManager($metadataDriver);
        $cmf = $this->createClassMetadataFactory($em, $type);
        $metadata = $cmf->getAllMetadata();

        $metadata[0]->name = ExportedUser::class;

        self::assertEquals(ExportedUser::class, $metadata[0]->name);

        $type = $this->getType();
        $cme = new ClassMetadataExporter();
        $exporter = $cme->getExporter($type, __DIR__ . '/export/' . $type);

        if ($type === 'annotation') {
            $exporter->setEntityGenerator(new EntityGenerator());
        }

        $this->extension = $exporter->getExtension();

        $exporter->setMetadata($metadata);
        $exporter->export();

        if ($type == 'annotation') {
            self::assertTrue(file_exists(__DIR__ . '/export/' . $type . '/'.str_replace('\\', '/', ExportedUser::class).$this->extension));
        } else {
            self::assertTrue(file_exists(__DIR__ . '/export/' . $type . '/Doctrine.Tests.ORM.Tools.Export.ExportedUser'.$this->extension));
        }
    }

    /**
     * @depends testExportDirectoryAndFilesAreCreated
     */
    public function testExportedMetadataCanBeReadBackIn()
    {
        $type = $this->getType();

        $metadataDriver = $this->createMetadataDriver($type, __DIR__ . '/export/' . $type);
        $em = $this->createEntityManager($metadataDriver);
        $cmf = $this->createClassMetadataFactory($em, $type);
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
        self::assertEquals(GeneratorType::IDENTITY, $class->generatorType, "Generator Type wrong");

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
        $property = $class->getProperty('address');

        self::assertNotNull($property);

        $joinColumns = $property->getJoinColumns();
        $joinColumn  = reset($joinColumns);

        self::assertEquals('Doctrine\Tests\ORM\Tools\Export\Address', $property->getTargetEntity());
        self::assertEquals('address_id', $joinColumn->getColumnName());
        self::assertEquals('id', $joinColumn->getReferencedColumnName());
        self::assertEquals('CASCADE', $joinColumn->getOnDelete());

        self::assertContains('remove', $property->getCascade());
        self::assertContains('persist', $property->getCascade());
        self::assertNotContains('refresh', $property->getCascade());
        self::assertNotContains('merge', $property->getCascade());
        self::assertNotContains('detach', $property->getCascade());
        self::assertTrue($property->isOrphanRemoval());
        self::assertEquals(FetchMode::EAGER, $property->getFetchMode());

        return $class;
    }

    /**
     * @depends testFieldsAreExported
     */
    public function testManyToOneAssociationsAreExported($class)
    {
        $property = $class->getProperty('mainGroup');

        self::assertNotNull($property);
        self::assertEquals(Group::class, $property->getTargetEntity());
    }

    /**
     * @depends testOneToOneAssociationsAreExported
     * @param ClassMetadata $class
     */
    public function testOneToManyAssociationsAreExported($class)
    {
        /** @var OneToManyAssociationMetadata $property */
        $property = $class->getProperty('phonenumbers');

        self::assertNotNull($property);

        self::assertInstanceOf(OneToManyAssociationMetadata::class, $property);
        self::assertEquals(Phonenumber::class, $property->getTargetEntity());
        self::assertEquals('user', $property->getMappedBy());
        self::assertEquals(['number' => 'ASC'], $property->getOrderBy());

        self::assertContains('remove', $property->getCascade());
        self::assertContains('persist', $property->getCascade());
        self::assertNotContains('refresh', $property->getCascade());
        self::assertContains('merge', $property->getCascade());
        self::assertNotContains('detach', $property->getCascade());
        self::assertTrue($property->isOrphanRemoval());
        self::assertEquals(FetchMode::LAZY, $property->getFetchMode());

        return $class;
    }

    /**
     * @depends testOneToManyAssociationsAreExported
     * @param ClassMetadata $metadata
     */
    public function testManyToManyAssociationsAreExported($class)
    {
        $property = $class->getProperty('groups');

        self::assertNotNull($property);

        $joinTable          = $property->getJoinTable();
        $joinColumns        = $joinTable->getJoinColumns();
        $joinColumn         = reset($joinColumns);
        $inverseJoinColumns = $joinTable->getInverseJoinColumns();
        $inverseJoinColumn  = reset($inverseJoinColumns);

        self::assertInstanceOf(ManyToManyAssociationMetadata::class, $property);
        self::assertEquals(Group::class, $property->getTargetEntity());
        self::assertEquals('cms_users_groups', $joinTable->getName());

        self::assertEquals('user_id', $joinColumn->getColumnName());
        self::assertEquals('id', $joinColumn->getReferencedColumnName());

        self::assertEquals('group_id', $inverseJoinColumn->getColumnName());
        self::assertEquals('id', $inverseJoinColumn->getReferencedColumnName());
        self::assertEquals('INT NULL', $inverseJoinColumn->getColumnDefinition());

        self::assertContains('remove', $property->getCascade());
        self::assertContains('persist', $property->getCascade());
        self::assertContains('refresh', $property->getCascade());
        self::assertContains('merge', $property->getCascade());
        self::assertContains('detach', $property->getCascade());

        self::assertEquals(FetchMode::EXTRA_LAZY, $property->getFetchMode());

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
        $property = $class->getProperty('phonenumbers');

        self::assertNotNull($property);

        self::assertContains('persist', $property->getCascade());
        self::assertContains('merge', $property->getCascade());
        self::assertContains('remove', $property->getCascade());
        self::assertNotContains('refresh', $property->getCascade());
        self::assertNotContains('detach', $property->getCascade());

        self::assertTrue($property->isOrphanRemoval());

        return $class;
    }

    /**
     * @depends testCascadeIsExported
     * @param ClassMetadata $class
     */
    public function testInversedByIsExported($class)
    {
        $property = $class->getProperty('address');

        self::assertNotNull($property);

        self::assertEquals('user', $property->getInversedBy());
    }
	/**
     * @depends testExportDirectoryAndFilesAreCreated
     */
    public function testCascadeAllCollapsed()
    {
        $type = $this->getType();

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
#        $this->deleteDirectory(__DIR__ . '/export/'.$this->getType());
    }

    protected function deleteDirectory($path)
    {
        if (is_file($path)) {
            return unlink($path);
        } else if (is_dir($path)) {
            $files = glob(rtrim($path,'/').'/*');

            if (is_array($files)) {
                foreach ($files as $file){
                    $this->deleteDirectory($file);
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
