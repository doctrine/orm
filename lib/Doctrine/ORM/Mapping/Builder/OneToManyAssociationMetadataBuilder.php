<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function array_merge;
use function array_unique;
use function assert;

class OneToManyAssociationMetadataBuilder extends ToManyAssociationMetadataBuilder
{
    /** @var Annotation\OneToMany */
    private $oneToManyAnnotation;

    public function withOneToManyAnnotation(Annotation\OneToMany $oneToManyAnnotation) : OneToManyAssociationMetadataBuilder
    {
        $this->oneToManyAnnotation = $oneToManyAnnotation;

        return $this;
    }

    /**
     * @internal Association metadata order of definition settings is important.
     */
    public function build() : Mapping\OneToManyAssociationMetadata
    {
        // Validate required fields
        assert($this->componentMetadata !== null);
        assert($this->oneToManyAnnotation !== null);
        assert($this->fieldName !== null);

        if (empty($this->oneToManyAnnotation->mappedBy)) {
            throw Mapping\MappingException::oneToManyRequiresMappedBy($this->fieldName);
        }

        $componentClassName  = $this->componentMetadata->getClassName();
        $associationMetadata = new Mapping\OneToManyAssociationMetadata($this->fieldName);

        $associationMetadata->setSourceEntity($componentClassName);
        $associationMetadata->setTargetEntity($this->getTargetEntity($this->oneToManyAnnotation->targetEntity));
        $associationMetadata->setCascade($this->getCascade($this->oneToManyAnnotation->cascade));
        $associationMetadata->setFetchMode($this->getFetchMode($this->oneToManyAnnotation->fetch));
        $associationMetadata->setOwningSide(false);
        $associationMetadata->setMappedBy($this->oneToManyAnnotation->mappedBy);

        if (! empty($this->oneToManyAnnotation->indexBy)) {
            $associationMetadata->setIndexedBy($this->oneToManyAnnotation->indexBy);
        }

        if ($this->oneToManyAnnotation->orphanRemoval) {
            $associationMetadata->setOrphanRemoval($this->oneToManyAnnotation->orphanRemoval);

            // Orphan removal also implies a cascade remove
            $associationMetadata->setCascade(array_unique(array_merge($associationMetadata->getCascade(), ['remove'])));
        }

        if ($this->orderByAnnotation !== null) {
            $associationMetadata->setOrderBy($this->orderByAnnotation->value);
        }

        $this->buildCache($associationMetadata);

        return $associationMetadata;
    }
}
