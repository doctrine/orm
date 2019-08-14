<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;

abstract class ToManyAssociationMetadataBuilder extends AssociationMetadataBuilder
{
    /** @var Annotation\OrderBy|null */
    protected $orderByAnnotation;

    public function withIdAnnotation(?Annotation\Id $idAnnotation) : ToManyAssociationMetadataBuilder
    {
        if ($idAnnotation !== null) {
            throw Mapping\MappingException::illegalToManyIdentifierAssociation(
                $this->componentMetadata->getClassName(),
                $this->fieldName
            );
        }

        return $this;
    }

    public function withOrderByAnnotation(?Annotation\OrderBy $orderByAnnotation) : ToManyAssociationMetadataBuilder
    {
        $this->orderByAnnotation = $orderByAnnotation;

        return $this;
    }
}
