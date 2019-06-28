<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function assert;
use function constant;
use function sprintf;
use function str_replace;
use function strtolower;
use function strtoupper;

class FieldMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var Mapping\ClassMetadata */
    private $componentMetadata;

    public function __construct(Mapping\ClassMetadataBuildingContext $metadataBuildingContext)
    {
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : FieldMetadataBuilder
    {
        $this->componentMetadata = $componentMetadata;

        return $this;
    }

    public function build() : Mapping\FieldMetadata
    {
        // Validate required fields
        assert($this->componentMetadata !== null);
        assert($this->cacheAnnotation !== null);

        $fieldMetadata = new Mapping\FieldMetadata();

        return $fieldMetadata;
    }
}
