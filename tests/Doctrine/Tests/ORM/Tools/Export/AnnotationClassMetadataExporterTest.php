<?php

namespace Doctrine\Tests\ORM\Tools\Export;

use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\Export\Driver\AnnotationExporter;

/**
 * Test case for AnnotationClassMetadataExporterTest
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class AnnotationClassMetadataExporterTest extends AbstractClassMetadataExporterTest
{
    protected function _getType()
    {
        return 'annotation';
    }

    /**
     * @group DDC-2632
     */
    public function testFKDefaultValueOptionExportAnnotationNoNullable() {
        $exporter = new AnnotationExporter();

        $metadata = $this->getMetadatasDCC2632Nonullable();

        $entityGenerator = new EntityGenerator();

        $entityGenerator->setAnnotationPrefix("");
        $exporter->setEntityGenerator($entityGenerator);

        $expetedResult = "@JoinColumn(name=\"user_id\",referencedColumnName=\"id\",nullable=false)";
        $this->assertContains($expetedResult,$string = trim(preg_replace('/\s+/', '', preg_replace('/\t/', '', $exporter->exportClassMetadata($metadata['Ddc2059Project'])))));
    }
    /**
     * @group DDC-2632
     */
    public function testFKDefaultValueOptionExportAnnotationNullable() {
        $exporter = new AnnotationExporter();

        $metadata = $this->getMetadatasDCC2632Nullable();

        $entityGenerator = new EntityGenerator();

        $entityGenerator->setAnnotationPrefix("");
        $exporter->setEntityGenerator($entityGenerator);

        $expetedResult = "@JoinColumn(name=\"user_id\",referencedColumnName=\"id\")";
        $this->assertContains($expetedResult,$string = trim(preg_replace('/\s+/', '', preg_replace('/\t/', '', $exporter->exportClassMetadata($metadata['Ddc2059Project'])))));
    }
}
