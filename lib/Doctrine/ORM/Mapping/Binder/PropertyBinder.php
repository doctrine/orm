<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Binder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;

class PropertyBinder
{
    /** @var Mapping\ComponentMetadata */
    private $metadata;

    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    public function __construct(
        Mapping\ComponentMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    )
    {
        $this->metadata                = $metadata;
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function bind(\ReflectionProperty $reflectionProperty, array $propertyAnnotations) : Mapping\Property
    {
        switch (true) {
            case isset($propertyAnnotations[Annotation\Column::class]):
                return $this->bindFieldMetadata($reflectionProperty, $propertyAnnotations);

            case isset($propertyAnnotations[Annotation\OneToOne::class]):
                return $this->convertReflectionPropertyToOneToOneAssociationMetadata(
                    $reflectionProperty,
                    $propertyAnnotations
                );

            case isset($propertyAnnotations[Annotation\ManyToOne::class]):
                return $this->convertReflectionPropertyToManyToOneAssociationMetadata(
                    $reflectionProperty,
                    $propertyAnnotations
                );

            case isset($propertyAnnotations[Annotation\OneToMany::class]):
                return $this->convertReflectionPropertyToOneToManyAssociationMetadata(
                    $reflectionProperty,
                    $propertyAnnotations
                );

            case isset($propertyAnnotations[Annotation\ManyToMany::class]):
                return $this->convertReflectionPropertyToManyToManyAssociationMetadata(
                    $reflectionProperty,
                    $propertyAnnotations
                );

            case isset($propertyAnnotations[Annotation\Embedded::class]):
                return null;

            default:
                return new Mapping\TransientMetadata($reflectionProperty->getName());
        }
    }

    private function bindFieldMetadata(
        \ReflectionProperty $reflectionProperty,
        array $propertyAnnotations
    ) : Mapping\FieldMetadata
    {
        $className   = $this->metadata->getClassName();
        $fieldName   = $reflectionProperty->getName();
        $isVersioned = isset($propertyAnnotations[Annotation\Version::class]);
        $columnAnnot = $propertyAnnotations[Annotation\Column::class];

        if ($columnAnnot->type === null) {
            throw Mapping\MappingException::propertyTypeIsRequired($className, $fieldName);
        }

        $fieldMetadata = new Mapping\FieldMetadata($fieldName);

        $fieldMetadata->setType(Type::getType($columnAnnot->type));
        $fieldMetadata->setVersioned($isVersioned);

        if (isset($propertyAnnotations[Annotation\Id::class])) {
            $fieldMetadata->setPrimaryKey(true);
        }

        if (! empty($columnAnnot->name)) {
            $fieldMetadata->setColumnName($columnAnnot->name);
        }

        if (! empty($columnAnnot->columnDefinition)) {
            $fieldMetadata->setColumnDefinition($columnAnnot->columnDefinition);
        }

        if (! empty($columnAnnot->length)) {
            $fieldMetadata->setLength($columnAnnot->length);
        }

        if ($columnAnnot->options) {
            $fieldMetadata->setOptions($columnAnnot->options);
        }

        $fieldMetadata->setScale($columnAnnot->scale);
        $fieldMetadata->setPrecision($columnAnnot->precision);
        $fieldMetadata->setNullable($columnAnnot->nullable);
        $fieldMetadata->setUnique($columnAnnot->unique);
    }
}
