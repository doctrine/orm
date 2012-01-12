<?php

namespace Doctrine\Tests\Mocks;

class MetadataDriverMock implements \Doctrine\ORM\Mapping\Driver\Driver
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