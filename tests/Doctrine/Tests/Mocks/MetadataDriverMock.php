<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataBuildingContext;
use Doctrine\ORM\Mapping\ComponentMetadata;
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
        ?ComponentMetadata $parent,
        ClassMetadataBuildingContext $metadataBuildingContext
    ) : ComponentMetadata {
        return new ClassMetadata($className, $parent, $metadataBuildingContext);
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className) : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllClassNames() : array
    {
        return [];
    }
}
