<?php

namespace Doctrine\Tests\ORM\Tools\Export;

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
}
