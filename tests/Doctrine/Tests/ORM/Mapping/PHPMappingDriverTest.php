<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\Common\Persistence\Mapping\Driver\PHPDriver,
    Doctrine\ORM\Tools\Export\ClassMetadataExporter;

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

    /**
     * @expectedException Doctrine\ORM\Cache\CacheException
     * @expectedExceptionMessage Entity association field "Doctrine\Tests\ORM\Mapping\PHPSLC#foo" not configured as part of the second-level cache.
     */
    public function testFailingSecondLevelCacheAssociation()
    {
        $className = 'Doctrine\Tests\ORM\Mapping\PHPSLC';
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata($className);
        $mappingDriver->loadMetadataForClass($className, $class);
    }
}
