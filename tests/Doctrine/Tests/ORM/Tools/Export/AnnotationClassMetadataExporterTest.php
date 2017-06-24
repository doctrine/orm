<?php

namespace Doctrine\Tests\ORM\Tools\Export;

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
     * @expectedException RuntimeException
     * @expectedExceptionMessage For the AnnotationExporter you must set an EntityGenerator instance with the setEntityGenerator() method.
     */
    public function testExportClassMetadataException()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();

        $annotationExporter = new AnnotationExporter();
        $annotationExporter->exportClassMetadata($metadata);
    }
}
