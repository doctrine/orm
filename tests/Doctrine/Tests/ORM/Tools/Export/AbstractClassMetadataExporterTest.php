<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\ORM\Tools\Export;

use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\Tests\Mocks\MetadataDriverMock;
use Doctrine\Tests\Mocks\DatabasePlatformMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

require_once __DIR__ . '/../../../TestInit.php';

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
abstract class AbstractClassMetadataExporterTest extends \Doctrine\Tests\OrmTestCase
{
    protected $_extension;

    abstract protected function _getType();

    protected function _createEntityManager($metadataDriver)
    {
        $driverMock = new DriverMock();
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(__DIR__ . '/../../Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $eventManager = new EventManager();
        $conn = new ConnectionMock(array(), $driverMock, $config, $eventManager);
        $mockDriver = new MetadataDriverMock();
        $config->setMetadataDriverImpl($metadataDriver);

        return EntityManagerMock::create($conn, $config, $eventManager);
    }

    protected function _createMetadataDriver($type, $path)
    {
        $mappingDriver = array(
            'php'        => 'Doctrine\Common\Persistence\Mapping\Driver\PHPDriver',
            'annotation' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
            'xml'        => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
            'yaml'       => 'Doctrine\ORM\Mapping\Driver\YamlDriver',
        );
        $this->assertArrayHasKey($type, $mappingDriver, "There is no metadata driver for the type '" . $type . "'.");
        $class = $mappingDriver[$type];

        if ($type === 'annotation') {
            $driver = $this->createAnnotationDriver(array($path));
        } else {
            $driver = new $class($path);
        }
        return $driver;
    }

    protected function _createClassMetadataFactory($em, $type)
    {
        if ($type === 'annotation') {
            $factory = new ClassMetadataFactory();
        } else {
            $factory = new DisconnectedClassMetadataFactory();
        }
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

        $metadata[0]->name = 'Doctrine\Tests\ORM\Tools\Export\ExportedUser';

        $this->assertEquals('Doctrine\Tests\ORM\Tools\Export\ExportedUser', $metadata[0]->name);

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
            $this->assertTrue(file_exists(__DIR__ . '/export/' . $type . '/'.str_replace('\\', '/', 'Doctrine\Tests\ORM\Tools\Export\ExportedUser').$this->_extension));
        } else {
            $this->assertTrue(file_exists(__DIR__ . '/export/' . $type . '/Doctrine.Tests.ORM.Tools.Export.ExportedUser'.$this->_extension));
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

        $this->assertEquals(1, count($metadata));

        $class = current($metadata);

        $this->assertEquals('Doctrine\Tests\ORM\Tools\Export\ExportedUser', $class->name);

        return $class;
    }

    /**
     * @depends testExportedMetadataCanBeReadBackIn
     * @param ClassMetadataInfo $class
     */
    public function testTableIsExported($class)
    {
        $this->assertEquals('cms_users', $class->table['name']);

        return $class;
    }

    /**
     * @depends testTableIsExported
     * @param ClassMetadataInfo $class
     */
    public function testTypeIsExported($class)
    {
        $this->assertFalse($class->isMappedSuperclass);

        return $class;
    }

    /**
     * @depends testTypeIsExported
     * @param ClassMetadataInfo $class
     */
    public function testIdentifierIsExported($class)
    {
        $this->assertEquals(ClassMetadataInfo::GENERATOR_TYPE_IDENTITY, $class->generatorType);
        $this->assertEquals(array('id'), $class->identifier);
        $this->assertTrue(isset($class->fieldMappings['id']['id']) && $class->fieldMappings['id']['id'] === true);

        return $class;
    }

    /**
     * @depends testIdentifierIsExported
     * @param ClassMetadataInfo $class
     */
    public function testFieldsAreExported($class)
    {
        $this->assertTrue(isset($class->fieldMappings['id']['id']) && $class->fieldMappings['id']['id'] === true);
        $this->assertEquals('id', $class->fieldMappings['id']['fieldName']);
        $this->assertEquals('integer', $class->fieldMappings['id']['type']);
        $this->assertEquals('id', $class->fieldMappings['id']['columnName']);

        $this->assertEquals('name', $class->fieldMappings['name']['fieldName']);
        $this->assertEquals('string', $class->fieldMappings['name']['type']);
        $this->assertEquals(50, $class->fieldMappings['name']['length']);
        $this->assertEquals('name', $class->fieldMappings['name']['columnName']);

        $this->assertEquals('email', $class->fieldMappings['email']['fieldName']);
        $this->assertEquals('string', $class->fieldMappings['email']['type']);
        $this->assertEquals('user_email', $class->fieldMappings['email']['columnName']);
        $this->assertEquals('CHAR(32) NOT NULL', $class->fieldMappings['email']['columnDefinition']);

        return $class;
    }

    /**
     * @depends testFieldsAreExported
     * @param ClassMetadataInfo $class
     */
    public function testOneToOneAssociationsAreExported($class)
    {
        $this->assertTrue(isset($class->associationMappings['address']));
        $this->assertEquals('Doctrine\Tests\ORM\Tools\Export\Address', $class->associationMappings['address']['targetEntity']);
        $this->assertEquals('address_id', $class->associationMappings['address']['joinColumns'][0]['name']);
        $this->assertEquals('id', $class->associationMappings['address']['joinColumns'][0]['referencedColumnName']);
        $this->assertEquals('CASCADE', $class->associationMappings['address']['joinColumns'][0]['onDelete']);

        $this->assertTrue($class->associationMappings['address']['isCascadeRemove']);
        $this->assertTrue($class->associationMappings['address']['isCascadePersist']);
        $this->assertFalse($class->associationMappings['address']['isCascadeRefresh']);
        $this->assertFalse($class->associationMappings['address']['isCascadeMerge']);
        $this->assertFalse($class->associationMappings['address']['isCascadeDetach']);
        $this->assertTrue($class->associationMappings['address']['orphanRemoval']);

        return $class;
    }

    /**
     * @depends testFieldsAreExported
     */
    public function testManyToOneAssociationsAreExported($class)
    {
        $this->assertTrue(isset($class->associationMappings['mainGroup']));
        $this->assertEquals('Doctrine\Tests\ORM\Tools\Export\Group', $class->associationMappings['mainGroup']['targetEntity']);
    }

    /**
     * @depends testOneToOneAssociationsAreExported
     * @param ClassMetadataInfo $class
     */
    public function testOneToManyAssociationsAreExported($class)
    {
        $this->assertTrue(isset($class->associationMappings['phonenumbers']));
        //$this->assertInstanceOf('Doctrine\ORM\Mapping\OneToManyMapping', $class->associationMappings['phonenumbers']);
        $this->assertEquals('Doctrine\Tests\ORM\Tools\Export\Phonenumber', $class->associationMappings['phonenumbers']['targetEntity']);
        $this->assertEquals('user', $class->associationMappings['phonenumbers']['mappedBy']);
        $this->assertEquals(array('number' => 'ASC'), $class->associationMappings['phonenumbers']['orderBy']);

        $this->assertTrue($class->associationMappings['phonenumbers']['isCascadeRemove']);
        $this->assertTrue($class->associationMappings['phonenumbers']['isCascadePersist']);
        $this->assertFalse($class->associationMappings['phonenumbers']['isCascadeRefresh']);
        $this->assertTrue($class->associationMappings['phonenumbers']['isCascadeMerge']);
        $this->assertFalse($class->associationMappings['phonenumbers']['isCascadeDetach']);
        $this->assertTrue($class->associationMappings['phonenumbers']['orphanRemoval']);

        return $class;
    }

    /**
     * @depends testOneToManyAssociationsAreExported
     * @param ClassMetadataInfo $metadata
     */
    public function testManyToManyAssociationsAreExported($class)
    {
        $this->assertTrue(isset($class->associationMappings['groups']));
        //$this->assertInstanceOf('Doctrine\ORM\Mapping\ManyToManyMapping', $class->associationMappings['groups']);
        $this->assertEquals('Doctrine\Tests\ORM\Tools\Export\Group', $class->associationMappings['groups']['targetEntity']);
        $this->assertEquals('cms_users_groups', $class->associationMappings['groups']['joinTable']['name']);

        $this->assertEquals('user_id', $class->associationMappings['groups']['joinTable']['joinColumns'][0]['name']);
        $this->assertEquals('id', $class->associationMappings['groups']['joinTable']['joinColumns'][0]['referencedColumnName']);

        $this->assertEquals('group_id', $class->associationMappings['groups']['joinTable']['inverseJoinColumns'][0]['name']);
        $this->assertEquals('id', $class->associationMappings['groups']['joinTable']['inverseJoinColumns'][0]['referencedColumnName']);
        $this->assertEquals('INT NULL', $class->associationMappings['groups']['joinTable']['inverseJoinColumns'][0]['columnDefinition']);

        $this->assertTrue($class->associationMappings['groups']['isCascadeRemove']);
        $this->assertTrue($class->associationMappings['groups']['isCascadePersist']);
        $this->assertTrue($class->associationMappings['groups']['isCascadeRefresh']);
        $this->assertTrue($class->associationMappings['groups']['isCascadeMerge']);
        $this->assertTrue($class->associationMappings['groups']['isCascadeDetach']);

        return $class;
    }

    /**
     * @depends testManyToManyAssociationsAreExported
     * @param ClassMetadataInfo $class
     */
    public function testLifecycleCallbacksAreExported($class)
    {
        $this->assertTrue(isset($class->lifecycleCallbacks['prePersist']));
        $this->assertEquals(2, count($class->lifecycleCallbacks['prePersist']));
        $this->assertEquals('doStuffOnPrePersist', $class->lifecycleCallbacks['prePersist'][0]);
        $this->assertEquals('doOtherStuffOnPrePersistToo', $class->lifecycleCallbacks['prePersist'][1]);

        $this->assertTrue(isset($class->lifecycleCallbacks['postPersist']));
        $this->assertEquals(1, count($class->lifecycleCallbacks['postPersist']));
        $this->assertEquals('doStuffOnPostPersist', $class->lifecycleCallbacks['postPersist'][0]);

        return $class;
    }

    /**
     * @depends testLifecycleCallbacksAreExported
     * @param ClassMetadataInfo $class
     */
    public function testCascadeIsExported($class)
    {
        $this->assertTrue($class->associationMappings['phonenumbers']['isCascadePersist']);
        $this->assertTrue($class->associationMappings['phonenumbers']['isCascadeMerge']);
        $this->assertTrue($class->associationMappings['phonenumbers']['isCascadeRemove']);
        $this->assertFalse($class->associationMappings['phonenumbers']['isCascadeRefresh']);
        $this->assertFalse($class->associationMappings['phonenumbers']['isCascadeDetach']);
        $this->assertTrue($class->associationMappings['phonenumbers']['orphanRemoval']);

        return $class;
    }

    /**
     * @depends testCascadeIsExported
     * @param ClassMetadataInfo $class
     */
    public function testInversedByIsExported($class)
    {
        $this->assertEquals('user', $class->associationMappings['address']['inversedBy']);
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
            $this->assertEquals(1, count($nodes));

            $this->assertEquals('cascade-all', $nodes[0]->getName());
        } elseif ($type == 'yaml') {

            $yaml = new \Symfony\Component\Yaml\Parser();
            $value = $yaml->parse(file_get_contents(__DIR__ . '/export/'.$type.'/Doctrine.Tests.ORM.Tools.Export.ExportedUser.dcm.yml'));

            $this->assertTrue(isset($value['Doctrine\Tests\ORM\Tools\Export\ExportedUser']['oneToMany']['interests']['cascade']));
            $this->assertEquals(1, count($value['Doctrine\Tests\ORM\Tools\Export\ExportedUser']['oneToMany']['interests']['cascade']));
            $this->assertEquals('all', $value['Doctrine\Tests\ORM\Tools\Export\ExportedUser']['oneToMany']['interests']['cascade'][0]);

        } else {
            $this->markTestSkipped('Test aviable only for '.$type.' dirver');
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
            foreach ($files as $file){
                $this->_deleteDirectory($file);
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
