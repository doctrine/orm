<?php

namespace Doctrine\Tests\Mocks;

class MetadataDriverMock implements \Doctrine\ORM\Mapping\Driver\Driver
{
    public function loadMetadataForClass($className, \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
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