<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function array_merge;
use function array_unique;
use function assert;

class ManyToManyAssociationMetadataBuilder extends ToManyAssociationMetadataBuilder
{
    /** @var JoinTableMetadataBuilder */
    private $joinTableMetadataBuilder;

    /** @var Annotation\ManyToMany */
    private $manyToManyAnnotation;

    /** @var Annotation\JoinTable|null */
    private $joinTableAnnotation;

    public function __construct(
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext,
        ?JoinTableMetadataBuilder $joinTableMetadataBuilder = null,
        ?CacheMetadataBuilder $cacheMetadataBuilder = null
    ) {
        parent::__construct($metadataBuildingContext, $cacheMetadataBuilder);

        $this->joinTableMetadataBuilder = $joinTableMetadataBuilder ?: new JoinTableMetadataBuilder($metadataBuildingContext);
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : AssociationMetadataBuilder
    {
        parent::withComponentMetadata($componentMetadata);

        $this->joinTableMetadataBuilder->withComponentMetadata($componentMetadata);

        return $this;
    }

    public function withFieldName(string $fieldName) : AssociationMetadataBuilder
    {
        parent::withFieldName($fieldName);

        $this->joinTableMetadataBuilder->withFieldName($fieldName);

        return $this;
    }

    public function withManyToManyAnnotation(Annotation\ManyToMany $manyToManyAnnotation) : ManyToManyAssociationMetadataBuilder
    {
        $this->manyToManyAnnotation = $manyToManyAnnotation;

        return $this;
    }

    public function withJoinTableAnnotation(?Annotation\JoinTable $joinTableAnnotation) : ManyToManyAssociationMetadataBuilder
    {
        $this->joinTableAnnotation = $joinTableAnnotation;

        if ($joinTableAnnotation !== null) {
            $this->joinTableMetadataBuilder->withJoinTableAnnotation($joinTableAnnotation);
        }

        return $this;
    }

    /**
     * @internal Association metadata order of definition settings is important.
     */
    public function build() : Mapping\ManyToManyAssociationMetadata
    {
        // Validate required fields
        \assert($this->componentMetadata !== null);
        \assert($this->manyToManyAnnotation !== null);
        \assert($this->fieldName !== null);

        $componentClassName  = $this->componentMetadata->getClassName();
        $associationMetadata = new Mapping\ManyToManyAssociationMetadata($this->fieldName);

        $associationMetadata->setSourceEntity($componentClassName);
        $associationMetadata->setTargetEntity($this->getTargetEntity($this->manyToManyAnnotation->targetEntity));
        $associationMetadata->setCascade($this->getCascade($this->manyToManyAnnotation->cascade));
        $associationMetadata->setFetchMode($this->getFetchMode($this->manyToManyAnnotation->fetch));
        $associationMetadata->setOwningSide(true);

        if (! empty($this->manyToManyAnnotation->mappedBy)) {
            $associationMetadata->setMappedBy($this->manyToManyAnnotation->mappedBy);
            $associationMetadata->setOwningSide(false);
        }

        if (! empty($this->manyToManyAnnotation->inversedBy)) {
            $associationMetadata->setInversedBy($this->manyToManyAnnotation->inversedBy);
        }

        if (! empty($this->manyToManyAnnotation->indexBy)) {
            $associationMetadata->setIndexedBy($this->manyToManyAnnotation->indexBy);
        }

        if ($this->manyToManyAnnotation->orphanRemoval) {
            $associationMetadata->setOrphanRemoval($this->manyToManyAnnotation->orphanRemoval);

            // Orphan removal also implies a cascade remove
            $associationMetadata->setCascade(\array_unique(\array_merge($associationMetadata->getCascade(), ['remove'])));
        }

        if ($this->orderByAnnotation !== null) {
            $associationMetadata->setOrderBy($this->orderByAnnotation->value);
        }

        $this->buildCache($associationMetadata);

        // Check for owning side to consider join column
        if (! $associationMetadata->isOwningSide()) {
            return $associationMetadata;
        }

        $this->buildJoinTable($associationMetadata);

        return $associationMetadata;
    }

    protected function buildJoinTable(Mapping\ManyToManyAssociationMetadata $associationMetadata) : void
    {
        $this->joinTableMetadataBuilder->withTargetEntity($associationMetadata->getTargetEntity());

        $associationMetadata->setJoinTable($this->joinTableMetadataBuilder->build());
    }
}
