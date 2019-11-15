<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Mapping;
use function assert;

class TransientMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var Mapping\ClassMetadata */
    private $componentMetadata;

    /** @var string */
    private $fieldName;

    public function __construct(Mapping\ClassMetadataBuildingContext $metadataBuildingContext)
    {
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : TransientMetadataBuilder
    {
        $this->componentMetadata = $componentMetadata;

        return $this;
    }

    public function withFieldName(string $fieldName) : TransientMetadataBuilder
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function build() : Mapping\TransientMetadata
    {
        // Validate required fields
        \assert($this->componentMetadata !== null);
        \assert($this->fieldName !== null);

        $transientMetadata = new Mapping\TransientMetadata($this->fieldName);

        $transientMetadata->setDeclaringClass($this->componentMetadata);

        return $transientMetadata;
    }
}
