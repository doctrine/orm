<?php

namespace Doctrine\Tests\Mocks;

class MetadataDriverMock implements \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
{
    public function loadMetadataForClass($className, \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata)
    {
        return;
    }

    public function isTransient($className)
    {
        return false;
    }

    public function getAllClassNames()
    {
        return array();
    }
}