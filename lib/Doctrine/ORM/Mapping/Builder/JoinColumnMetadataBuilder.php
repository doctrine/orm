<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function assert;
use function strtoupper;

class JoinColumnMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var Mapping\ClassMetadata */
    private $componentMetadata;

    /** @var string */
    private $fieldName;

    /** @var Annotation\JoinColumn|null */
    private $joinColumnAnnotation;

    public function __construct(Mapping\ClassMetadataBuildingContext $metadataBuildingContext)
    {
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : JoinColumnMetadataBuilder
    {
        $this->componentMetadata = $componentMetadata;

        return $this;
    }

    public function withFieldName(string $fieldName) : JoinColumnMetadataBuilder
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function withJoinColumnAnnotation(?Annotation\JoinColumn $joinColumnAnnotation) : JoinColumnMetadataBuilder
    {
        $this->joinColumnAnnotation = $joinColumnAnnotation;

        return $this;
    }

    public function build() : Mapping\JoinColumnMetadata
    {
        // Validate required fields
        \assert($this->componentMetadata !== null);
        \assert($this->fieldName !== null);

        $componentClassName = $this->componentMetadata->getClassName();
        $namingStrategy     = $this->metadataBuildingContext->getNamingStrategy();
        $joinColumnMetadata = new Mapping\JoinColumnMetadata();

        if (! $this->componentMetadata->isMappedSuperclass) {
            $joinColumnMetadata->setTableName($this->componentMetadata->getTableName());
        }

        $joinColumnMetadata->setColumnName($namingStrategy->joinColumnName($this->fieldName, $componentClassName));
        $joinColumnMetadata->setReferencedColumnName($namingStrategy->referenceColumnName());

        if ($this->joinColumnAnnotation === null) {
            return $joinColumnMetadata;
        }

        $joinColumnMetadata->setNullable($this->joinColumnAnnotation->nullable);
        $joinColumnMetadata->setUnique($this->joinColumnAnnotation->unique);

        if (! empty($this->joinColumnAnnotation->name)) {
            $joinColumnMetadata->setColumnName($this->joinColumnAnnotation->name);
        }

        if (! empty($this->joinColumnAnnotation->referencedColumnName)) {
            $joinColumnMetadata->setReferencedColumnName($this->joinColumnAnnotation->referencedColumnName);
        }

        if (! empty($this->joinColumnAnnotation->fieldName)) {
            $joinColumnMetadata->setAliasedName($this->joinColumnAnnotation->fieldName);
        }

        if (! empty($this->joinColumnAnnotation->columnDefinition)) {
            $joinColumnMetadata->setColumnDefinition($this->joinColumnAnnotation->columnDefinition);
        }

        if ($this->joinColumnAnnotation->onDelete) {
            $joinColumnMetadata->setOnDelete(\strtoupper($this->joinColumnAnnotation->onDelete));
        }

        return $joinColumnMetadata;
    }
}
