<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\Common\Persistence\Mapping\Driver\PHPDriver,
    Doctrine\ORM\Tools\Export\ClassMetadataExporter;

require_once __DIR__ . '/../../TestInit.php';

class PHPMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'php';

        // Convert Annotation mapping information to PHP
        // Uncomment this code if annotations changed and you want to update the PHP code
        // for the same mapping information
//        $meta = new \Doctrine\ORM\Mapping\ClassMetadataInfo("Doctrine\Tests\ORM\Mapping\Animal");
//        $driver = $this->createAnnotationDriver();
//        $driver->loadMetadataForClass("Doctrine\Tests\ORM\Mapping\Animal", $meta);
//        $exporter = $cme->getExporter('php', $path);
//        echo $exporter->exportClassMetadata($meta);

        return new PHPDriver($path);
    }

    /**
     * All class are entitier for php driver
     *
     * @group DDC-889
     */
    public function testinvalidEntityOrMappedSuperClassShouldMentionParentClasses()
    {
        $this->createClassMetadata('Doctrine\Tests\Models\DDC889\DDC889Class');
    }
}