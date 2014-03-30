<?php

namespace Doctrine\Tests\Mocks;

/**
 * Mock class for MappingDriver.
 */
class MetadataDriverMock implements \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
{
    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllClassNames()
    {
        return array();
    }
}
