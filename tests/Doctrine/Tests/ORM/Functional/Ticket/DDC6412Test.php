<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\DDC6412\DDC6412File;
use Doctrine\Tests\OrmTestCase;

/**
 * @author JarJak
 */
class DDC6412Test extends OrmTestCase
{
    public function testGetSingleIdentifierFieldName_NoIdEntity_ThrowsException()
    {
        $cm = new ClassMetadata(DDC6412File::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);
        $cm->getSingleIdentifierFieldName();
    }
}