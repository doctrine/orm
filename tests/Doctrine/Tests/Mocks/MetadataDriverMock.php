<?php

namespace Doctrine\Tests\Mocks;

class MetadataDriverMock
{
    public function loadMetadataForClass($className, \Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        return;
    }

    public function isTransient($className)
    {
        return false;
    }

    public function preload()
    {
        return array();
    }
}