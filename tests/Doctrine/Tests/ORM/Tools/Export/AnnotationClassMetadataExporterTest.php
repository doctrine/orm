<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Export;

/**
 * Test case for AnnotationClassMetadataExporterTest
 *
 * @link        http://www.phpdoctrine.org
 */
class AnnotationClassMetadataExporterTest extends ClassMetadataExporterTestCase
{
    protected function getType(): string
    {
        return 'annotation';
    }
}
