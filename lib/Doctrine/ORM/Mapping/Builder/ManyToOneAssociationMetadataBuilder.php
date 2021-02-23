<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function assert;

class ManyToOneAssociationMetadataBuilder extends ToOneAssociationMetadataBuilder
{
    /** @var Annotation\ManyToOne */
    private $manyToOneAnnotation;

    public function withManyToOneAnnotation(Annotation\ManyToOne $manyToOneAnnotation) : ManyToOneAssociationMetadataBuilder
    {
        $this->manyToOneAnnotation = $manyToOneAnnotation;

        return $this;
    }

    /**
     * @internal Association metadata order of definition settings is important.
     */
    public function build() : Mapping\ManyToOneAssociationMetadata
    {
        // Validate required fields
        assert($this->componentMetadata !== null);
        assert($this->manyToOneAnnotation !== null);
        assert($this->fieldName !== null);

        $componentClassName  = $this->componentMetadata->getClassName();
        $associationMetadata = new Mapping\ManyToOneAssociationMetadata($this->fieldName);

        $associationMetadata->setSourceEntity($componentClassName);
        $associationMetadata->setTargetEntity($this->getTargetEntity($this->manyToOneAnnotation->targetEntity));
        $associationMetadata->setCascade($this->getCascade($this->manyToOneAnnotation->cascade));
        $associationMetadata->setFetchMode($this->getFetchMode($this->manyToOneAnnotation->fetch));
        $associationMetadata->setOwningSide(true);

        if (! empty($this->manyToOneAnnotation->inversedBy)) {
            $associationMetadata->setInversedBy($this->manyToOneAnnotation->inversedBy);
        }

        $this->buildCache($associationMetadata);
        $this->buildPrimaryKey($associationMetadata);
        $this->buildJoinColumns($associationMetadata);

        return $associationMetadata;
    }
}
