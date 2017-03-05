<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Mock class for MappingDriver.
 */
class MetadataDriverMock implements MappingDriver
{
    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
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
        return [];
    }
}
