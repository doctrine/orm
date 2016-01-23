<?php

namespace Shitty\Tests\Mocks;

/**
 * Mock class for MappingDriver.
 */
class MetadataDriverMock implements \Shitty\Common\Persistence\Mapping\Driver\MappingDriver
{
    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, \Shitty\Common\Persistence\Mapping\ClassMetadata $metadata)
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
