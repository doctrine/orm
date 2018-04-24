<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Binder;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
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

    /**
     * @throws DBALException
     */
    private function bindFieldMetadata(
        \ReflectionProperty $reflectionProperty,
        array $propertyAnnotations
    ) : Mapping\FieldMetadata {
        $className        = $this->metadata->getClassName();
        $fieldName        = $reflectionProperty->getName();
        $isVersioned      = isset($propertyAnnotations[Annotation\Version::class]);
        $columnAnnotation = $propertyAnnotations[Annotation\Column::class];

        $fieldMetadata = new Mapping\FieldMetadata($fieldName);

        if ($columnAnnotation->type === null) {
            throw Mapping\MappingException::propertyTypeIsRequired($className, $fieldName);
        }

        $this->attachLocalColumnMetadata($fieldMetadata, $columnAnnotation);

        $fieldMetadata->setVersioned($isVersioned);

        if (isset($propertyAnnotations[Annotation\Id::class])) {
            $fieldMetadata->setPrimaryKey(true);
        }
    }


    /**
     * @throws DBALException
     */
    private function attachLocalColumnMetadata(
        Mapping\LocalColumnMetadata $localColumnMetadata,
        Annotation\Column $columnAnnotation
    ) : void {
        $this->attachColumnMetadata($localColumnMetadata, $columnAnnotation);

        $localColumnMetadata->setScale($columnAnnotation->scale);
        $localColumnMetadata->setPrecision($columnAnnotation->precision);

        if (! empty($columnAnnotation->length)) {
            $localColumnMetadata->setLength($columnAnnotation->length);
        }
    }

    /**
     * @throws DBALException
     */
    private function attachColumnMetadata(
        Mapping\ColumnMetadata $columnMetadata,
        Annotation\Column $columnAnnotation
    ) : void {
        $columnMetadata->setType(Type::getType($columnAnnotation->type));
        $columnMetadata->setNullable($columnAnnotation->nullable);
        $columnMetadata->setUnique($columnAnnotation->unique);

        if (! empty($columnAnnotation->name)) {
            $columnMetadata->setColumnName($columnAnnotation->name);
        }

        if (! empty($columnAnnotation->columnDefinition)) {
            $columnMetadata->setColumnDefinition($columnAnnotation->columnDefinition);
        }

        if ($columnAnnotation->options) {
            $columnMetadata->setOptions($columnAnnotation->options);
        }
    }
}
