<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Binder;

use Doctrine\ORM\Mapping;

class PropertyBinder
{
    /** @var Mapping\ComponentMetadata */
    private $metadata;

    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    public function __construct(
        Mapping\ComponentMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    )
    {
        $this->metadata                = $metadata;
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function bind()
    {
    }
}