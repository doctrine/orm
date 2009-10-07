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
class ClassMetadataExporterTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * Test that we can get the different types of exporters
     */
    public function testGetExporter()
    {
        $cme = new ClassMetadataExporter();

        $exporter = $cme->getExporter('xml');
        $this->assertTrue($exporter instanceof \Doctrine\ORM\Tools\Export\Driver\XmlExporter);

        $exporter = $cme->getExporter('yml');
        $this->assertTrue($exporter instanceof \Doctrine\ORM\Tools\Export\Driver\YamlExporter);

        $exporter = $cme->getExporter('annotation');
        $this->assertTrue($exporter instanceof \Doctrine\ORM\Tools\Export\Driver\AnnotationExporter);
    }

    /**
     * Test that we can add mapping directories for the different types of
     * mapping information.
     */
    public function testAddMappingDirectory()
    {
        $cme = new ClassMetadataExporter();
        $cme->addMappingSource(__DIR__ . '/annotation', 'annotation');
        $cme->addMappingSource(__DIR__ . '/php', 'php');
        $cme->addMappingSource(__DIR__ . '/xml', 'xml');
        $cme->addMappingSource(__DIR__ . '/yml', 'yml');

        $mappingSources = $cme->getMappingSources();
        $this->assertEquals(4, count($mappingSources));

        $this->assertEquals($mappingSources[0][0], __DIR__.'/annotation');
        $this->assertTrue($mappingSources[0][1] instanceof \Doctrine\ORM\Mapping\Driver\AnnotationDriver);

        $this->assertEquals($mappingSources[1][0], __DIR__.'/php');
        $this->assertEquals('php', $mappingSources[1][1]);

        $this->assertEquals($mappingSources[2][0], __DIR__.'/xml');
        $this->assertTrue($mappingSources[2][1] instanceof \Doctrine\ORM\Mapping\Driver\XmlDriver);

        $this->assertEquals($mappingSources[3][0], __DIR__.'/yml');
        $this->assertTrue($mappingSources[3][1] instanceof \Doctrine\ORM\Mapping\Driver\YamlDriver);
    }

    /**
     * Test that we can add mapping directories then retrieve all the defined
     * ClassMetadata instances that are defined in the directories
     */
    public function testGetMetadataInstances()
    {
        $cme = new ClassMetadataExporter();
        $cme->addMappingSource(__DIR__ . '/php', 'php');
        $cme->addMappingSource(__DIR__ . '/xml', 'xml');
        $cme->addMappingSource(__DIR__ . '/yml', 'yml');

        $metadataInstances = $cme->getMetadatasForMappingSources();

        $this->assertEquals(3, count($metadataInstances));
        $this->assertEquals('PhpTest', $metadataInstances['PhpTest']->name);
        $this->assertEquals('XmlTest', $metadataInstances['XmlTest']->name);
        $this->assertEquals('YmlTest', $metadataInstances['YmlTest']->name);
    }

    /**
     * Test that we can export mapping directories to another format and that
     * the exported data can then be read back in properly.
     */
    public function testExport()
    {
        $exportDir = __DIR__ . '/export';

        if ( ! is_dir($exportDir)) {
            mkdir($exportDir, 0777, true);
        }

        $types = array('annotation', 'php', 'xml', 'yml');

        $cme = new ClassMetadataExporter();
        $cme->addMappingSource(__DIR__ . '/php', 'php');
        $cme->addMappingSource(__DIR__ . '/xml', 'xml');
        $cme->addMappingSource(__DIR__ . '/yml', 'yml');

        foreach ($types as $type) {
            // Export the above mapping directories to the type
            $exporter = $cme->getExporter($type, __DIR__ . '/export/' . $type);
            $exporter->setMetadatas($cme->getMetadatasForMappingSources());
            $exporter->export();

            // Make sure the files were written
            $this->assertTrue(file_exists(__DIR__ . '/export/' . $type . '/PhpTest'.$exporter->getExtension()));
            $this->assertTrue(file_exists(__DIR__ . '/export/' . $type . '/XmlTest'.$exporter->getExtension()));
            $this->assertTrue(file_exists(__DIR__ . '/export/' . $type . '/YmlTest'.$exporter->getExtension()));

            // Try and read back in the exported mapping files to make sure they are valid
            $cme2 = new ClassMetadataExporter();
            $cme2->addMappingSource(__DIR__ . '/export/' . $type, $type);
            $metadataInstances = $cme2->getMetadatasForMappingSources();
            $this->assertEquals(3, count($metadataInstances));
            $this->assertEquals('PhpTest', $metadataInstances['PhpTest']->name);
            $this->assertEquals('XmlTest', $metadataInstances['XmlTest']->name);
            $this->assertEquals('YmlTest', $metadataInstances['YmlTest']->name);

            // Cleanup
            unlink(__DIR__ . '/export/' . $type . '/PhpTest'.$exporter->getExtension());
            unlink(__DIR__ . '/export/' . $type . '/XmlTest'.$exporter->getExtension());
            unlink(__DIR__ . '/export/' . $type . '/YmlTest'.$exporter->getExtension());
            rmdir(__DIR__ . '/export/'.$type);
        }
        rmdir(__DIR__ . '/export');
    }
}