<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function assert;

class JoinTableMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var Mapping\ClassMetadata */
    private $entityClassMetadata;

    /** @var Annotation\JoinTable|null */
    private $tableAnnotation;

    public function __construct(Mapping\ClassMetadataBuildingContext $metadataBuildingContext)
    {
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function withEntityClassMetadata(Mapping\ClassMetadata $entityClassMetadata) : JoinTableMetadataBuilder
    {
        $this->entityClassMetadata = $entityClassMetadata;

        return $this;
    }

    public function withTableAnnotation(?Annotation\Table $tableAnnotation) : JoinTableMetadataBuilder
    {
        $this->tableAnnotation = $tableAnnotation;

        return $this;
    }

    public function build() : Mapping\JoinTableMetadata
    {
        // Validate required fields
        assert($this->entityClassMetadata !== null);

        $namingStrategy = $this->metadataBuildingContext->getNamingStrategy();
    }
}