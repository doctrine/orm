<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\Tests\Models\DDC889\DDC889Class;
use Doctrine\Tests\ORM\Mapping;

class PHPMappingDriverTest extends AbstractMappingDriverTest
{
    protected function loadDriver(): MappingDriver
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
        self::assertInstanceOf(ClassMetadata::class, $this->createClassMetadata(DDC889Class::class));
    }

    public function testFailingSecondLevelCacheAssociation()
    {
        $this->expectException('Doctrine\ORM\Cache\CacheException');
        $this->expectExceptionMessage('Entity association field "Doctrine\Tests\ORM\Mapping\PHPSLC#foo" not configured as part of the second-level cache.');
        $mappingDriver = $this->loadDriver();

        $class = new ClassMetadata(Mapping\PHPSLC::class);
        $mappingDriver->loadMetadataForClass(Mapping\PHPSLC::class, $class);
    }
}
