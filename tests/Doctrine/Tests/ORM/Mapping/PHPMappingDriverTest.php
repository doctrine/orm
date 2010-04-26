<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\Driver\PHPDriver,
    Doctrine\ORM\Tools\Export\ClassMetadataExporter;

require_once __DIR__ . '/../../TestInit.php';

class PHPMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'php';

        /*
        // Convert YAML mapping information to PHP
        // Uncomment this code if the YAML changes and you want to update the PHP code
        // for the same mapping information
        $cme = new ClassMetadataExporter();
        $cme->addMappingSource(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');

        $exporter = $cme->getExporter('php', $path);
        $exporter->setMetadatas($cme->getMetadatas());
        $exporter->export();
        */

        return new PHPDriver($path);
    }
}