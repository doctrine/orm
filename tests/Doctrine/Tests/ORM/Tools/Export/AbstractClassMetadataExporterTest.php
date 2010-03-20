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

use Doctrine\ORM\Tools\Export\ClassMetadataExporter,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Tools\EntityGenerator;

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

    protected function _getTestEntityName()
    {
        if ($this->_getType() == 'annotation') {
            return 'Doctrine\Tests\ORM\Tools\Export\User2';
        } else {
            return 'Doctrine\Tests\ORM\Tools\Export\User';
        }
    }

    protected function _loadClassMetadataExporter()
    {
        $type = $this->_getType();

        $cme = new ClassMetadataExporter();
        $cme->addMappingSource(__DIR__ . '/' . $type, $type);

        return $cme;
    }

    public function testGetMetadatasForMappingSources()
    {
        $type = $this->_getType();
        $cme = $this->_loadClassMetadataExporter();
        $metadataInstances = $cme->getMetadatas();

        $this->assertEquals('Doctrine\Tests\ORM\Tools\Export\User', $metadataInstances['Doctrine\Tests\ORM\Tools\Export\User']->name);

        return $cme;
    }

    /**
     * @depends testGetMetadatasForMappingSources
     * @param ClassMetadataExporter $cme
     */
    public function testExportDirectoryAndFilesAreCreated($cme)
    {
        $type = $this->_getType();
        $exporter = $cme->getExporter($type, __DIR__ . '/export/' . $type);
        if ($type === 'annotation') {
            $entityGenerator = new EntityGenerator();
            $exporter->setEntityGenerator($entityGenerator);
        }
        $this->_extension = $exporter->getExtension();
        $metadatas = $cme->getMetadatas();
        if ($type == 'annotation') {
            $metadatas['Doctrine\Tests\ORM\Tools\Export\User']->name = $this->_getTestEntityName();
        }

        $exporter->setMetadatas($metadatas);
        $exporter->export();

        if ($type == 'annotation') {
            $this->assertTrue(file_exists(__DIR__ . '/export/' . $type . '/'.str_replace('\\', '/', $this->_getTestEntityName()).$this->_extension));
        } else {
            $this->assertTrue(file_exists(__DIR__ . '/export/' . $type . '/Doctrine.Tests.ORM.Tools.Export.User'.$this->_extension));
        }
    }

    /**
     * @depends testExportDirectoryAndFilesAreCreated
     */
    public function testExportedMetadataCanBeReadBackIn()
    {
        $type = $this->_getType();
        $cme = new ClassMetadataExporter();
        $cme->addMappingSource(__DIR__ . '/export/' . $type, $type);

        $metadataInstances = $cme->getMetadatas();
        $metadata = current($metadataInstances);
    
        $this->assertEquals($this->_getTestEntityName(), $metadata->name);

        return $metadata;
    }

    /**
     * @depends testExportedMetadataCanBeReadBackIn
     * @param ClassMetadataInfo $metadata
     */
    public function testTableIsExported($metadata)
    {
        $this->assertEquals('cms_users', $metadata->primaryTable['name']);

        return $metadata;
    }

    /**
     * @depends testTableIsExported
     * @param ClassMetadataInfo $metadata
     */
    public function testTypeIsExported($metadata)
    {
        $this->assertFalse($metadata->isMappedSuperclass);

        return $metadata;
    }

    /**
     * @depends testTypeIsExported
     * @param ClassMetadataInfo $metadata
     */
    public function testIdentifierIsExported($metadata)
    {
        $this->assertEquals(ClassMetadataInfo::GENERATOR_TYPE_AUTO, $metadata->generatorType);
        $this->assertEquals(array('id'), $metadata->identifier);
        $this->assertTrue(isset($metadata->fieldMappings['id']['id']) && $metadata->fieldMappings['id']['id'] === true);

        return $metadata;
    }

    /**
     * @depends testIdentifierIsExported
     * @param ClassMetadataInfo $metadata
     */
    public function testFieldsAreExpored($metadata)
    {
        $this->assertTrue(isset($metadata->fieldMappings['id']['id']) && $metadata->fieldMappings['id']['id'] === true);
        $this->assertEquals('id', $metadata->fieldMappings['id']['fieldName']);
        $this->assertEquals('integer', $metadata->fieldMappings['id']['type']);
        $this->assertEquals('id', $metadata->fieldMappings['id']['columnName']);

        $this->assertEquals('name', $metadata->fieldMappings['name']['fieldName']);
        $this->assertEquals('string', $metadata->fieldMappings['name']['type']);
        $this->assertEquals(50, $metadata->fieldMappings['name']['length']);
        $this->assertEquals('name', $metadata->fieldMappings['name']['columnName']);

        $this->assertEquals('email', $metadata->fieldMappings['email']['fieldName']);
        $this->assertEquals('string', $metadata->fieldMappings['email']['type']);
        $this->assertEquals('user_email', $metadata->fieldMappings['email']['columnName']);
        $this->assertEquals('CHAR(32) NOT NULL', $metadata->fieldMappings['email']['columnDefinition']);

        return $metadata;
    }

    /**
     * @depends testFieldsAreExpored
     * @param ClassMetadataInfo $metadata
     */
    public function testOneToOneAssociationsAreExported($metadata)
    {
        $this->assertTrue(isset($metadata->associationMappings['address']));
        $this->assertTrue($metadata->associationMappings['address'] instanceof \Doctrine\ORM\Mapping\OneToOneMapping);
        $this->assertEquals('Doctrine\Tests\ORM\Tools\Export\Address', $metadata->associationMappings['address']->targetEntityName);
        $this->assertEquals('address_id', $metadata->associationMappings['address']->joinColumns[0]['name']);
        $this->assertEquals('id', $metadata->associationMappings['address']->joinColumns[0]['referencedColumnName']);
        $this->assertEquals('CASCADE', $metadata->associationMappings['address']->joinColumns[0]['onDelete']);
        $this->assertEquals('CASCADE', $metadata->associationMappings['address']->joinColumns[0]['onUpdate']);

        $this->assertTrue($metadata->associationMappings['address']->isCascadeRemove);
        $this->assertFalse($metadata->associationMappings['address']->isCascadePersist);
        $this->assertFalse($metadata->associationMappings['address']->isCascadeRefresh);
        $this->assertFalse($metadata->associationMappings['address']->isCascadeMerge);
        $this->assertFalse($metadata->associationMappings['address']->isCascadeDetach);

        return $metadata;
    }

    /**
     * @depends testOneToOneAssociationsAreExported
     * @param ClassMetadataInfo $metadata
     */
    public function testOneToManyAssociationsAreExported($metadata)
    {
        $this->assertTrue(isset($metadata->associationMappings['phonenumbers']));
        $this->assertTrue($metadata->associationMappings['phonenumbers'] instanceof \Doctrine\ORM\Mapping\OneToManyMapping);
        $this->assertEquals('Doctrine\Tests\ORM\Tools\Export\Phonenumber', $metadata->associationMappings['phonenumbers']->targetEntityName);
        $this->assertEquals('user', $metadata->associationMappings['phonenumbers']->mappedBy);
        $this->assertEquals(array('number' => 'ASC'), $metadata->associationMappings['phonenumbers']->orderBy);

        $this->assertFalse($metadata->associationMappings['phonenumbers']->isCascadeRemove);
        $this->assertTrue($metadata->associationMappings['phonenumbers']->isCascadePersist);
        $this->assertFalse($metadata->associationMappings['phonenumbers']->isCascadeRefresh);
        $this->assertFalse($metadata->associationMappings['phonenumbers']->isCascadeMerge);
        $this->assertFalse($metadata->associationMappings['phonenumbers']->isCascadeDetach);
        
        return $metadata;
    }

    /**
     * @depends testOneToManyAssociationsAreExported
     * @param ClassMetadataInfo $metadata
     */
    public function testManyToManyAssociationsAreExported($metadata)
    {
        $this->assertTrue(isset($metadata->associationMappings['groups']));
        $this->assertTrue($metadata->associationMappings['groups'] instanceof \Doctrine\ORM\Mapping\ManyToManyMapping);
        $this->assertEquals('Doctrine\Tests\ORM\Tools\Export\Group', $metadata->associationMappings['groups']->targetEntityName);
        $this->assertEquals('cms_users_groups', $metadata->associationMappings['groups']->joinTable['name']);

        $this->assertEquals('user_id', $metadata->associationMappings['groups']->joinTable['joinColumns'][0]['name']);
        $this->assertEquals('id', $metadata->associationMappings['groups']->joinTable['joinColumns'][0]['referencedColumnName']);

        $this->assertEquals('group_id', $metadata->associationMappings['groups']->joinTable['inverseJoinColumns'][0]['name']);
        $this->assertEquals('id', $metadata->associationMappings['groups']->joinTable['inverseJoinColumns'][0]['referencedColumnName']);
        $this->assertEquals('INT NULL', $metadata->associationMappings['groups']->joinTable['inverseJoinColumns'][0]['columnDefinition']);

        $this->assertTrue($metadata->associationMappings['groups']->isCascadeRemove);
        $this->assertTrue($metadata->associationMappings['groups']->isCascadePersist);
        $this->assertTrue($metadata->associationMappings['groups']->isCascadeRefresh);
        $this->assertTrue($metadata->associationMappings['groups']->isCascadeMerge);
        $this->assertTrue($metadata->associationMappings['groups']->isCascadeDetach);

        return $metadata;
    }

    /**
     * @depends testManyToManyAssociationsAreExported
     * @param ClassMetadataInfo $metadata
     */
    public function testLifecycleCallbacksAreExported($metadata)
    {
        $this->assertTrue(isset($metadata->lifecycleCallbacks['prePersist']));
        $this->assertEquals(2, count($metadata->lifecycleCallbacks['prePersist']));
        $this->assertEquals('doStuffOnPrePersist', $metadata->lifecycleCallbacks['prePersist'][0]);
        $this->assertEquals('doOtherStuffOnPrePersistToo', $metadata->lifecycleCallbacks['prePersist'][1]);

        $this->assertTrue(isset($metadata->lifecycleCallbacks['postPersist']));
        $this->assertEquals(1, count($metadata->lifecycleCallbacks['postPersist']));
        $this->assertEquals('doStuffOnPostPersist', $metadata->lifecycleCallbacks['postPersist'][0]);

        return $metadata;
    }

    public function __destruct()
    {
        $type = $this->_getType();
        $this->_deleteDirectory(__DIR__ . '/export/'.$this->_getType());
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