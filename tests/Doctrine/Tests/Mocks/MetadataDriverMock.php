<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataBuildingContext;
use Doctrine\ORM\Mapping\Driver\MappingDriver;

/**
 * Mock class for MappingDriver.
 */
class MetadataDriverMock implements MappingDriver
{
    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass(
        string $className,
        ClassMetadata $metadata,
        ClassMetadataBuildingContext $metadataBuildingContext
    ) {
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
