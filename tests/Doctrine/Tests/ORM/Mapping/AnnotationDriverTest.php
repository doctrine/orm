<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Events;

require_once __DIR__ . '/../../TestInit.php';

class AnnotationDriverTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @group DDC-268
     */
    public function testLoadMetadataForNonEntityThrowsException()
    {
        $cm = new ClassMetadata('stdClass');
        $reader = new \Doctrine\Common\Annotations\AnnotationReader(new \Doctrine\Common\Cache\ArrayCache());
        $annotationDriver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader);

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');
        $annotationDriver->loadMetadataForClass('stdClass', $cm);
    }

    /**
     * @group DDC-268
     */
    public function testColumnWithMissingTypeDefaultsToString()
    {
        $cm = new ClassMetadata('Doctrine\Tests\ORM\Mapping\InvalidColumn');
        $reader = new \Doctrine\Common\Annotations\AnnotationReader(new \Doctrine\Common\Cache\ArrayCache());
        $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
        $annotationDriver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader);

        $annotationDriver->loadMetadataForClass('Doctrine\Tests\ORM\Mapping\InvalidColumn', $cm);
        $this->assertEquals('string', $cm->fieldMappings['id']['type']);
    }
}

/**
 * @Entity
 */
class InvalidColumn
{
    /** @Id @Column */
    public $id;
}

