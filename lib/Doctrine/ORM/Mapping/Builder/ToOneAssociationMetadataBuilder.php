<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Cache\Exception\NonCacheableEntityAssociation;
use Doctrine\ORM\Mapping;
use function count;
use function in_array;

abstract class ToOneAssociationMetadataBuilder extends AssociationMetadataBuilder
{
    /** @var JoinColumnMetadataBuilder */
    protected $joinColumnMetadataBuilder;

    /** @var Annotation\Id|null */
    protected $idAnnotation;

    /** @var Annotation\JoinColumn|null */
    protected $joinColumnAnnotation;

    /** @var Annotation\JoinColumns|null */
    protected $joinColumnsAnnotation;

    public function __construct(
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext,
        ?JoinColumnMetadataBuilder $joinColumnMetadataBuilder = null,
        ?CacheMetadataBuilder $cacheMetadataBuilder = null
    ) {
        parent::__construct($metadataBuildingContext, $cacheMetadataBuilder);

        $this->joinColumnMetadataBuilder = $joinColumnMetadataBuilder ?: new JoinColumnMetadataBuilder($metadataBuildingContext);
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : AssociationMetadataBuilder
    {
        parent::withComponentMetadata($componentMetadata);

        $this->joinColumnMetadataBuilder->withComponentMetadata($componentMetadata);

        return $this;
    }

    public function withFieldName(string $fieldName) : AssociationMetadataBuilder
    {
        parent::withFieldName($fieldName);

        $this->joinColumnMetadataBuilder->withFieldName($fieldName);

        return $this;
    }

    public function withIdAnnotation(?Annotation\Id $idAnnotation) : ToOneAssociationMetadataBuilder
    {
        $this->idAnnotation = $idAnnotation;

        return $this;
    }

    public function withJoinColumnAnnotation(?Annotation\JoinColumn $joinColumnAnnotation) : ToOneAssociationMetadataBuilder
    {
        $this->joinColumnAnnotation = $joinColumnAnnotation;

        return $this;
    }

    public function withJoinColumnsAnnotation(?Annotation\JoinColumns $joinColumnsAnnotation) : ToOneAssociationMetadataBuilder
    {
        $this->joinColumnsAnnotation = $joinColumnsAnnotation;

        return $this;
    }

    protected function buildPrimaryKey(Mapping\AssociationMetadata $associationMetadata) : void
    {
        if ($this->idAnnotation !== null) {
            if ($associationMetadata->isOrphanRemoval()) {
                throw Mapping\MappingException::illegalOrphanRemovalOnIdentifierAssociation(
                    $this->componentMetadata->getClassName(),
                    $this->fieldName
                );
            }

            if (! $associationMetadata->isOwningSide()) {
                throw Mapping\MappingException::illegalInverseIdentifierAssociation(
                    $this->componentMetadata->getClassName(),
                    $this->fieldName
                );
            }

            if ($this->componentMetadata->getCache() !== null && $associationMetadata->getCache() === null) {
                throw NonCacheableEntityAssociation::fromEntityAndField(
                    $this->componentMetadata->getClassName(),
                    $this->fieldName
                );
            }

            // @todo guilhermeblanco The below block of code modifies component metadata properties, and it should be moved
            //                       to the component metadata builder instead of here.

            if (! in_array($this->fieldName, $this->componentMetadata->identifier, true)) {
                $this->componentMetadata->identifier[] = $this->fieldName;
            }

            $associationMetadata->setPrimaryKey(true);
        }
    }

    protected function buildJoinColumns(Mapping\ToOneAssociationMetadata $associationMetadata) : void
    {
        switch (true) {
            case $this->joinColumnsAnnotation !== null:
                foreach ($this->joinColumnsAnnotation->value as $joinColumnAnnotation) {
                    $this->joinColumnMetadataBuilder->withJoinColumnAnnotation($joinColumnAnnotation);

                    $associationMetadata->addJoinColumn($this->joinColumnMetadataBuilder->build());
                }

                // Prevent currently unsupported scenario: association with multiple columns and being marked as primary
                if ($associationMetadata->isPrimaryKey() && count($associationMetadata->getJoinColumns()) > 1) {
                    throw Mapping\MappingException::cannotMapCompositePrimaryKeyEntitiesAsForeignId(
                        $this->componentMetadata->getClassName(),
                        $associationMetadata->getTargetEntity(),
                        $this->fieldName
                    );
                }

                break;

            case $this->joinColumnAnnotation !== null:
                $this->joinColumnMetadataBuilder->withJoinColumnAnnotation($this->joinColumnAnnotation);

                $associationMetadata->addJoinColumn($this->joinColumnMetadataBuilder->build());
                break;

            default:
                $joinColumnMetadata = $this->joinColumnMetadataBuilder->build();

                $associationMetadata->addJoinColumn($this->joinColumnMetadataBuilder->build());
                break;
        }
    }
}
