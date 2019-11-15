<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function assert;

class PropertyMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var FieldMetadataBuilder */
    private $fieldMetadataBuilder;

    /** @var OneToOneAssociationMetadataBuilder */
    private $oneToOneAssociationMetadataBuilder;

    /** @var ManyToOneAssociationMetadataBuilder */
    private $manyToOneAssociationMetadataBuilder;

    /** @var OneToManyAssociationMetadataBuilder */
    private $oneToManyAssociationMetadataBuilder;

    /** @var ManyToManyAssociationMetadataBuilder */
    private $manyToManyAssociationMetadataBuilder;

    /** @var TransientMetadataBuilder */
    private $transientMetadataBuilder;

    /** @var Mapping\ClassMetadata */
    private $componentMetadata;

    /** @var string */
    private $fieldName;

    /** @var Annotation\Id|null */
    private $idAnnotation;

    /** @var Annotation\Version|null */
    private $versionAnnotation;

    /** @var Annotation\Cache|null */
    private $cacheAnnotation;

    /** @var Annotation\Column|null */
    private $columnAnnotation;

    /** @var Annotation\Embedded|null */
    private $embeddedAnnotation;

    /** @var Annotation\OneToOne|null */
    private $oneToOneAnnotation;

    /** @var Annotation\ManyToOne|null */
    private $manyToOneAnnotation;

    /** @var Annotation\OneToMany|null */
    private $oneToManyAnnotation;

    /** @var Annotation\ManyToMany|null */
    private $manyToManyAnnotation;

    /** @var Annotation\JoinTable|null */
    private $joinTableAnnotation;

    /** @var Annotation\JoinColumns|null */
    private $joinColumnsAnnotation;

    /** @var Annotation\JoinColumn|null */
    private $joinColumnAnnotation;

    /** @var Annotation\OrderBy|null */
    private $orderByAnnotation;

    /** @var Annotation\GeneratedValue|null */
    private $generatedValueAnnotation;

    /** @var Annotation\SequenceGenerator|null */
    private $sequenceGeneratorAnnotation;

    /** @var Annotation\CustomIdGenerator|null */
    private $customIdGeneratorAnnotation;

    public function __construct(
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext,
        ?FieldMetadataBuilder $fieldMetadataBuilder = null,
        ?OneToOneAssociationMetadataBuilder $oneToOneAssociationMetadataBuilder = null,
        ?ManyToOneAssociationMetadataBuilder $manyToOneAssociationMetadataBuilder = null,
        ?OneToManyAssociationMetadataBuilder $oneToManyAssociationMetadataBuilder = null,
        ?ManyToManyAssociationMetadataBuilder $manyToManyAssociationMetadataBuilder = null,
        ?TransientMetadataBuilder $transientMetadataBuilder = null
    ) {
        $this->metadataBuildingContext              = $metadataBuildingContext;
        $this->fieldMetadataBuilder                 = $fieldMetadataBuilder ?: new FieldMetadataBuilder($metadataBuildingContext);
        $this->oneToOneAssociationMetadataBuilder   = $oneToOneAssociationMetadataBuilder ?: new OneToOneAssociationMetadataBuilder($metadataBuildingContext);
        $this->manyToOneAssociationMetadataBuilder  = $manyToOneAssociationMetadataBuilder ?: new ManyToOneAssociationMetadataBuilder($metadataBuildingContext);
        $this->oneToManyAssociationMetadataBuilder  = $oneToManyAssociationMetadataBuilder ?: new OneToManyAssociationMetadataBuilder($metadataBuildingContext);
        $this->manyToManyAssociationMetadataBuilder = $manyToManyAssociationMetadataBuilder ?: new ManyToManyAssociationMetadataBuilder($metadataBuildingContext);
        $this->transientMetadataBuilder             = $transientMetadataBuilder ?: new TransientMetadataBuilder($metadataBuildingContext);
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : PropertyMetadataBuilder
    {
        $this->componentMetadata = $componentMetadata;

        $this->fieldMetadataBuilder->withComponentMetadata($componentMetadata);
        $this->oneToOneAssociationMetadataBuilder->withComponentMetadata($componentMetadata);
        $this->manyToOneAssociationMetadataBuilder->withComponentMetadata($componentMetadata);
        $this->oneToManyAssociationMetadataBuilder->withComponentMetadata($componentMetadata);
        $this->manyToManyAssociationMetadataBuilder->withComponentMetadata($componentMetadata);
        $this->transientMetadataBuilder->withComponentMetadata($componentMetadata);

        return $this;
    }

    public function withFieldName(string $fieldName) : PropertyMetadataBuilder
    {
        $this->fieldName = $fieldName;

        $this->fieldMetadataBuilder->withFieldName($fieldName);
        $this->oneToOneAssociationMetadataBuilder->withFieldName($fieldName);
        $this->manyToOneAssociationMetadataBuilder->withFieldName($fieldName);
        $this->oneToManyAssociationMetadataBuilder->withFieldName($fieldName);
        $this->manyToManyAssociationMetadataBuilder->withFieldName($fieldName);
        $this->transientMetadataBuilder->withFieldName($fieldName);

        return $this;
    }

    public function withIdAnnotation(?Annotation\Id $idAnnotation) : PropertyMetadataBuilder
    {
        $this->idAnnotation = $idAnnotation;

        $this->fieldMetadataBuilder->withIdAnnotation($idAnnotation);
        $this->oneToOneAssociationMetadataBuilder->withIdAnnotation($idAnnotation);
        $this->manyToOneAssociationMetadataBuilder->withIdAnnotation($idAnnotation);

        return $this;
    }

    public function withCacheAnnotation(?Annotation\Cache $cacheAnnotation) : PropertyMetadataBuilder
    {
        $this->cacheAnnotation = $cacheAnnotation;

        $this->oneToOneAssociationMetadataBuilder->withCacheAnnotation($cacheAnnotation);
        $this->manyToOneAssociationMetadataBuilder->withCacheAnnotation($cacheAnnotation);
        $this->oneToManyAssociationMetadataBuilder->withCacheAnnotation($cacheAnnotation);
        $this->manyToManyAssociationMetadataBuilder->withCacheAnnotation($cacheAnnotation);

        return $this;
    }

    public function withColumnAnnotation(?Annotation\Column $columnAnnotation) : PropertyMetadataBuilder
    {
        $this->columnAnnotation = $columnAnnotation;

        if ($columnAnnotation !== null) {
            // Make sure all other property type annotations are cleared
            $this->embeddedAnnotation   = null;
            $this->oneToOneAnnotation   = null;
            $this->manyToOneAnnotation  = null;
            $this->oneToManyAnnotation  = null;
            $this->manyToManyAnnotation = null;

            $this->fieldMetadataBuilder->withColumnAnnotation($columnAnnotation);
        }

        return $this;
    }

    public function withEmbeddedAnnotation(?Annotation\Embedded $embeddedAnnotation) : PropertyMetadataBuilder
    {
        $this->embeddedAnnotation = $embeddedAnnotation;

        if ($embeddedAnnotation !== null) {
            // Make sure all other property type annotations are cleared
            $this->columnAnnotation     = null;
            $this->oneToOneAnnotation   = null;
            $this->manyToOneAnnotation  = null;
            $this->oneToManyAnnotation  = null;
            $this->manyToManyAnnotation = null;

            // $this->embeddedMetadataBuilder->withEmbeddedAnnotation($embeddedAnnotation);
        }

        return $this;
    }

    public function withOneToOneAnnotation(?Annotation\OneToOne $oneToOneAnnotation) : PropertyMetadataBuilder
    {
        $this->oneToOneAnnotation = $oneToOneAnnotation;

        if ($oneToOneAnnotation !== null) {
            // Make sure all other property type annotations are cleared
            $this->columnAnnotation     = null;
            $this->embeddedAnnotation   = null;
            $this->manyToOneAnnotation  = null;
            $this->oneToManyAnnotation  = null;
            $this->manyToManyAnnotation = null;

            $this->oneToOneAssociationMetadataBuilder->withOneToOneAnnotation($oneToOneAnnotation);
        }

        return $this;
    }

    public function withManyToOneAnnotation(?Annotation\ManyToOne $manyToOneAnnotation) : PropertyMetadataBuilder
    {
        $this->manyToOneAnnotation = $manyToOneAnnotation;

        if ($manyToOneAnnotation !== null) {
            // Make sure all other property type annotations are cleared
            $this->columnAnnotation     = null;
            $this->embeddedAnnotation   = null;
            $this->oneToOneAnnotation   = null;
            $this->oneToManyAnnotation  = null;
            $this->manyToManyAnnotation = null;

            $this->manyToOneAssociationMetadataBuilder->withManyToOneAnnotation($manyToOneAnnotation);
        }

        return $this;
    }

    public function withOneToManyAnnotation(?Annotation\OneToMany $oneToManyAnnotation) : PropertyMetadataBuilder
    {
        $this->oneToManyAnnotation = $oneToManyAnnotation;

        if ($oneToManyAnnotation !== null) {
            // Make sure all other property type annotations are cleared
            $this->columnAnnotation     = null;
            $this->embeddedAnnotation   = null;
            $this->oneToOneAnnotation   = null;
            $this->manyToOneAnnotation  = null;
            $this->manyToManyAnnotation = null;

            $this->oneToManyAssociationMetadataBuilder->withOneToManyAnnotation($oneToManyAnnotation);
        }

        return $this;
    }

    public function withManyToManyAnnotation(?Annotation\ManyToMany $manyToManyAnnotation) : PropertyMetadataBuilder
    {
        $this->manyToManyAnnotation = $manyToManyAnnotation;

        if ($manyToManyAnnotation !== null) {
            // Make sure all other property type annotations are cleared
            $this->columnAnnotation    = null;
            $this->embeddedAnnotation  = null;
            $this->oneToOneAnnotation  = null;
            $this->manyToOneAnnotation = null;
            $this->oneToManyAnnotation = null;

            $this->manyToManyAssociationMetadataBuilder->withManyToManyAnnotation($manyToManyAnnotation);
        }

        return $this;
    }

    public function withJoinTableAnnotation(?Annotation\JoinTable $joinTableAnnotation) : PropertyMetadataBuilder
    {
        $this->joinTableAnnotation = $joinTableAnnotation;

        $this->manyToManyAssociationMetadataBuilder->withJoinTableAnnotation($joinTableAnnotation);

        return $this;
    }

    public function withJoinColumnsAnnotation(?Annotation\JoinColumns $joinColumnsAnnotation) : PropertyMetadataBuilder
    {
        $this->joinColumnsAnnotation = $joinColumnsAnnotation;

        $this->oneToOneAssociationMetadataBuilder->withJoinColumnsAnnotation($joinColumnsAnnotation);
        $this->manyToOneAssociationMetadataBuilder->withJoinColumnsAnnotation($joinColumnsAnnotation);

        return $this;
    }

    public function withJoinColumnAnnotation(?Annotation\JoinColumn $joinColumnAnnotation) : PropertyMetadataBuilder
    {
        $this->joinColumnAnnotation = $joinColumnAnnotation;

        $this->oneToOneAssociationMetadataBuilder->withJoinColumnAnnotation($joinColumnAnnotation);
        $this->manyToOneAssociationMetadataBuilder->withJoinColumnAnnotation($joinColumnAnnotation);

        return $this;
    }

    public function withOrderByAnnotation(?Annotation\OrderBy $orderByAnnotation) : PropertyMetadataBuilder
    {
        $this->orderByAnnotation = $orderByAnnotation;

        $this->oneToManyAssociationMetadataBuilder->withOrderByAnnotation($orderByAnnotation);
        $this->manyToManyAssociationMetadataBuilder->withOrderByAnnotation($orderByAnnotation);

        return $this;
    }

    public function withVersionAnnotation(?Annotation\Version $versionAnnotation) : PropertyMetadataBuilder
    {
        $this->versionAnnotation = $versionAnnotation;

        $this->fieldMetadataBuilder->withVersionAnnotation($versionAnnotation);

        return $this;
    }

    public function withGeneratedValueAnnotation(?Annotation\GeneratedValue $generatedValueAnnotation) : PropertyMetadataBuilder
    {
        $this->generatedValueAnnotation = $generatedValueAnnotation;

        $this->fieldMetadataBuilder->withGeneratedValueAnnotation($this->generatedValueAnnotation);

        return $this;
    }

    public function withSequenceGeneratorAnnotation(?Annotation\SequenceGenerator $sequenceGeneratorAnnotation) : PropertyMetadataBuilder
    {
        $this->sequenceGeneratorAnnotation = $sequenceGeneratorAnnotation;

        $this->fieldMetadataBuilder->withSequenceGeneratorAnnotation($this->sequenceGeneratorAnnotation);

        return $this;
    }

    public function withCustomIdGeneratorAnnotation(?Annotation\CustomIdGenerator $customIdGeneratorAnnotation) : PropertyMetadataBuilder
    {
        $this->customIdGeneratorAnnotation = $customIdGeneratorAnnotation;

        $this->fieldMetadataBuilder->withCustomIdGeneratorAnnotation($this->customIdGeneratorAnnotation);

        return $this;
    }

    public function build() : ?Mapping\Property
    {
        // Validate required fields
        \assert($this->componentMetadata !== null);
        \assert($this->fieldName !== null);

        $componentClassName = $this->componentMetadata->getClassName();

        switch (true) {
            case $this->columnAnnotation !== null:
                $propertyMetadata = $this->fieldMetadataBuilder->build();

                // Prevent column duplication
                $columnName = $propertyMetadata->getColumnName();

                if ($this->componentMetadata->checkPropertyDuplication($columnName)) {
                    throw Mapping\MappingException::duplicateColumnName($componentClassName, $columnName);
                }

                return $propertyMetadata;
            case $this->embeddedAnnotation !== null:
                // @todo guilhermeblanco Remove nullable typehint once embeddeds are back
                return null;
            case $this->oneToOneAnnotation !== null:
                return $this->oneToOneAssociationMetadataBuilder->build();

                // Prevent column duplication
                // @todo guilhermeblanco Open an issue to discuss making this scenario impossible.
//                foreach ($propertyMetadata->getJoinColumns() as $joinColumnMetadata) {
//                    $columnName = $joinColumnMetadata->getColumnName();
//
//                    if ($this->componentMetadata->checkPropertyDuplication($columnName)) {
//                        throw Mapping\MappingException::duplicateColumnName($componentClassName, $columnName);
//                    }
//                }
//
//                return $propertyMetadata;
            case $this->manyToOneAnnotation !== null:
                return $this->manyToOneAssociationMetadataBuilder->build();

                // Prevent column duplication
                // @todo guilhermeblanco Open an issue to discuss making this scenario impossible.
//                foreach ($propertyMetadata->getJoinColumns() as $joinColumnMetadata) {
//                    $columnName = $joinColumnMetadata->getColumnName();
//
//                    if ($this->componentMetadata->checkPropertyDuplication($columnName)) {
//                        throw Mapping\MappingException::duplicateColumnName($componentClassName, $columnName);
//                    }
//                }
//
//                return $propertyMetadata;
            case $this->oneToManyAnnotation !== null:
                $propertyMetadata = $this->oneToManyAssociationMetadataBuilder->build();

                if ($this->componentMetadata->isMappedSuperclass && ! $propertyMetadata->isOwningSide()) {
                    throw Mapping\MappingException::illegalToManyAssociationOnMappedSuperclass(
                        $componentClassName,
                        $this->fieldName
                    );
                }

                return $propertyMetadata;
            case $this->manyToManyAnnotation !== null:
                $propertyMetadata = $this->manyToManyAssociationMetadataBuilder->build();

                if ($this->componentMetadata->isMappedSuperclass && ! $propertyMetadata->isOwningSide()) {
                    throw Mapping\MappingException::illegalToManyAssociationOnMappedSuperclass(
                        $componentClassName,
                        $this->fieldName
                    );
                }

                return $propertyMetadata;
            default:
                return $this->transientMetadataBuilder->build();
        }
    }
}
