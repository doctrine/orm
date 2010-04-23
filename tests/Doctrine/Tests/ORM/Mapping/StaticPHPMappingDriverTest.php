<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\Driver\StaticPHPDriver,
    Doctrine\ORM\Tools\Export\ClassMetadataExporter;

require_once __DIR__ . '/../../TestInit.php';

class StaticPHPMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        return new StaticPHPDriver(__DIR__ . DIRECTORY_SEPARATOR . 'php');
    }
}