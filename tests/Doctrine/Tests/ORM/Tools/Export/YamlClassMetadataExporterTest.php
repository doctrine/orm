<?php

namespace Doctrine\Tests\ORM\Tools\Export;

use Doctrine\ORM\Tools\Export\Driver\YamlExporter;

/**
 * Test case for YamlClassMetadataExporterTest
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class YamlClassMetadataExporterTest extends AbstractClassMetadataExporterTest
{
    protected function _getType()
    {
        if (!class_exists('Symfony\Component\Yaml\Yaml', true)) {
            $this->markTestSkipped('Please install Symfony YAML Component into the include path of your PHP installation.');
        }

        return 'yaml';
    }

    /**
     * @group DDC-2632
     */
    public function testFKDefaultValueOptionExportYamlNoNullable() {
        $exporter = new YamlExporter();

        $metadata = $this->getMetadatasDCC2632Nonullable();

        $expectedPattern = "/joinColumns:\\s*user_id:\\s*referencedColumnName: id\\s*nullable: false/";
        $this->assertRegExp($expectedPattern,$exporter->exportClassMetadata($metadata['Ddc2059Project']));
    }
    /**
     * @group DDC-2632
     */
    public function testFKDefaultValueOptionExportYamlNullable() {
        $exporter = new YamlExporter();

        $metadata = $this->getMetadatasDCC2632Nullable();

        $expectedPattern = "/joinColumns:\\s*user_id:\\s*referencedColumnName: id/";
        $this->assertRegExp($expectedPattern,$exporter->exportClassMetadata($metadata['Ddc2059Project']));
    }

}
