<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * Mock class for MappingDriver.
 */
class MetadataDriverMock implements MappingDriver
{
    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function isTransient($className): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllClassNames(): array
    {
        return [];
    }
}
