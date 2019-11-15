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

    /** @var JoinColumnMetadataBuilder */
    private $joinColumnMetadataBuilder;

    /** @var Mapping\ClassMetadata */
    private $componentMetadata;

    /** @var string */
    private $targetEntity;

    /** @var string */
    private $fieldName;

    /** @var Annotation\JoinTable|null */
    private $joinTableAnnotation;

    public function __construct(
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext,
        ?JoinColumnMetadataBuilder $joinColumnMetadataBuilder = null
    ) {
        $this->metadataBuildingContext   = $metadataBuildingContext;
        $this->joinColumnMetadataBuilder = $joinColumnMetadataBuilder ?: new JoinColumnMetadataBuilder($metadataBuildingContext);
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : JoinTableMetadataBuilder
    {
        $this->componentMetadata = $componentMetadata;

        $this->joinColumnMetadataBuilder->withComponentMetadata($componentMetadata);

        return $this;
    }

    public function withTargetEntity(string $targetEntity) : JoinTableMetadataBuilder
    {
        $this->targetEntity = $targetEntity;

        return $this;
    }

    public function withFieldName(string $fieldName) : JoinTableMetadataBuilder
    {
        $this->fieldName = $fieldName;

        $this->joinColumnMetadataBuilder->withFieldName($fieldName);

        return $this;
    }

    public function withJoinTableAnnotation(?Annotation\JoinTable $joinTableAnnotation) : JoinTableMetadataBuilder
    {
        $this->joinTableAnnotation = $joinTableAnnotation;

        return $this;
    }

    public function build() : Mapping\JoinTableMetadata
    {
        // Validate required fields
        \assert($this->componentMetadata !== null);
        \assert($this->targetEntity !== null);
        \assert($this->fieldName !== null);

        $namingStrategy    = $this->metadataBuildingContext->getNamingStrategy();
        $souceEntity       = $this->componentMetadata->getClassName();
        $joinTableMetadata = new Mapping\JoinTableMetadata();

        $joinTableMetadata->setName($namingStrategy->joinTableName($souceEntity, $this->targetEntity, $this->fieldName));

        if ($this->joinTableAnnotation !== null) {
            if (! empty($this->joinTableAnnotation->name)) {
                $joinTableMetadata->setName($this->joinTableAnnotation->name);
            }

            if (! empty($this->joinTableAnnotation->schema)) {
                $joinTableMetadata->setSchema($this->joinTableAnnotation->schema);
            }

            /** @var Annotation\JoinColumn $joinColumnAnnotation */
            foreach ($this->joinTableAnnotation->joinColumns as $joinColumnAnnotation) {
                // Auto-inject on-delete, column name and referenced column name if not present
                $referencedColumnName = $joinColumnAnnotation->referencedColumnName ?? $namingStrategy->referenceColumnName();
                $columnName           = $joinColumnAnnotation->name ?? $namingStrategy->joinKeyColumnName($souceEntity, $referencedColumnName);

                $joinColumnAnnotation->onDelete             = $joinColumnAnnotation->onDelete ?? 'CASCADE';
                $joinColumnAnnotation->referencedColumnName = $referencedColumnName;
                $joinColumnAnnotation->name                 = $columnName;

                $this->joinColumnMetadataBuilder->withJoinColumnAnnotation($joinColumnAnnotation);

                $joinTableMetadata->addJoinColumn($this->joinColumnMetadataBuilder->build());
            }

            foreach ($this->joinTableAnnotation->inverseJoinColumns as $joinColumnAnnotation) {
                // Auto-inject on-delete, column name and referenced column name if not present
                $referencedColumnName = $joinColumnAnnotation->referencedColumnName ?? $namingStrategy->referenceColumnName();
                $columnName           = $joinColumnAnnotation->name ?? $namingStrategy->joinKeyColumnName($this->targetEntity, $referencedColumnName);

                $joinColumnAnnotation->onDelete             = $joinColumnAnnotation->onDelete ?? 'CASCADE';
                $joinColumnAnnotation->referencedColumnName = $referencedColumnName;
                $joinColumnAnnotation->name                 = $columnName;

                $this->joinColumnMetadataBuilder->withJoinColumnAnnotation($joinColumnAnnotation);

                $joinTableMetadata->addInverseJoinColumn($this->joinColumnMetadataBuilder->build());
            }
        }

        $selfReferenceEntity = $souceEntity === $this->targetEntity;

        if (! $joinTableMetadata->getJoinColumns()) {
            $joinColumnAnnotation = new Annotation\JoinColumn();

            $joinColumnAnnotation->onDelete             = 'CASCADE';
            $joinColumnAnnotation->referencedColumnName = $namingStrategy->referenceColumnName();
            $joinColumnAnnotation->name                 = $namingStrategy->joinKeyColumnName(
                $souceEntity,
                $selfReferenceEntity ? 'source' : $joinColumnAnnotation->referencedColumnName
            );

            $this->joinColumnMetadataBuilder->withJoinColumnAnnotation($joinColumnAnnotation);

            $joinTableMetadata->addJoinColumn($this->joinColumnMetadataBuilder->build());
        }

        if (! $joinTableMetadata->getInverseJoinColumns()) {
            $joinColumnAnnotation = new Annotation\JoinColumn();

            $joinColumnAnnotation->onDelete             = 'CASCADE';
            $joinColumnAnnotation->referencedColumnName = $namingStrategy->referenceColumnName();
            $joinColumnAnnotation->name                 = $namingStrategy->joinKeyColumnName(
                $this->targetEntity,
                $selfReferenceEntity ? 'target' : $joinColumnAnnotation->referencedColumnName
            );

            $this->joinColumnMetadataBuilder->withJoinColumnAnnotation($joinColumnAnnotation);

            $joinTableMetadata->addInverseJoinColumn($this->joinColumnMetadataBuilder->build());
        }

        return $joinTableMetadata;
    }
}
