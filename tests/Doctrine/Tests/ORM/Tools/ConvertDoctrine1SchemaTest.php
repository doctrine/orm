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

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Doctrine\ORM\Tools\ConvertDoctrine1Schema;
use Doctrine\Tests\Mocks\MetadataDriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\Tests\OrmTestCase;

/**
 * Test case for converting a Doctrine 1 style schema to Doctrine 2 mapping files
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class ConvertDoctrine1SchemaTest extends OrmTestCase
{
    protected function _createEntityManager($metadataDriver)
    {
        $driverMock = new DriverMock();
        $config = new Configuration();
        $config->setProxyDir(__DIR__ . '/../../Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $eventManager = new EventManager();
        $conn = new ConnectionMock(array(), $driverMock, $config, $eventManager);
        $config->setMetadataDriverImpl($metadataDriver);

        return EntityManagerMock::create($conn, $config, $eventManager);
    }

    public function testTest()
    {
        if ( ! class_exists('Symfony\Component\Yaml\Yaml', true)) {
            $this->markTestSkipped('Please install Symfony YAML Component into the include path of your PHP installation.');
        }

        $cme = new ClassMetadataExporter();
        $converter = new ConvertDoctrine1Schema(__DIR__ . '/doctrine1schema');

        $exporter = $cme->getExporter('yml', __DIR__ . '/convert');
        $exporter->setOverwriteExistingFiles(true);
        $exporter->setMetadata($converter->getMetadata());
        $exporter->export();

        self::assertTrue(file_exists(__DIR__ . '/convert/User.dcm.yml'));
        self::assertTrue(file_exists(__DIR__ . '/convert/Profile.dcm.yml'));

        $metadataDriver = new YamlDriver(__DIR__ . '/convert');
        $em = $this->_createEntityManager($metadataDriver);
        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        $metadata = $cmf->getAllMetadata();
        $profileClass = $cmf->getMetadataFor('Profile');
        $userClass = $cmf->getMetadataFor('User');

        self::assertEquals(2, count($metadata));

        self::assertEquals('User', $userClass->name);
        self::assertEquals(5, count($userClass->getProperties()));
        self::assertNotNull($userClass->getProperty('clob'));
        self::assertNotNull($userClass->getProperty('theAlias'));
        self::assertEquals('text', $userClass->getProperty('clob')->getTypeName());
        self::assertEquals('test_alias', $userClass->getProperty('theAlias')->getColumnName());

        self::assertEquals('Profile', $profileClass->name);
        self::assertEquals(4, count($profileClass->getProperties()));
        self::assertEquals('Profile', $profileClass->associationMappings['User']['sourceEntity']);
        self::assertEquals('User', $profileClass->associationMappings['User']['targetEntity']);

        self::assertEquals('username', $userClass->table['uniqueConstraints']['username']['columns'][0]);
    }

    public function tearDown()
    {
        @unlink(__DIR__ . '/convert/User.dcm.yml');
        @unlink(__DIR__ . '/convert/Profile.dcm.yml');
        @rmdir(__DIR__ . '/convert');
    }
}
