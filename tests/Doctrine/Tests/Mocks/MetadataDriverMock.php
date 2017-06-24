<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\ClassMetadata;

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
