<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Export;

use function class_exists;

/**
 * Test case for YamlClassMetadataExporterTest
 *
 * @link        http://www.phpdoctrine.org
 */
class YamlClassMetadataExporterTest extends AbstractClassMetadataExporterTest
{
    protected function getType(): string
    {
        if (! class_exists('Symfony\Component\Yaml\Yaml', true)) {
            $this->markTestSkipped('Please install Symfony YAML Component into the include path of your PHP installation.');
        }

        return 'yaml';
    }
}
