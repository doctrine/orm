<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function array_merge;
use function assert;

class FieldMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var Mapping\ClassMetadata */
    private $componentMetadata;

    /** @var string */
    private $fieldName;

    /** @var Annotation\Column */
    private $columnAnnotation;

    /** @var Annotation\Id|null */
    private $idAnnotation;

    /** @var Annotation\Version|null */
    private $versionAnnotation;

    public function __construct(Mapping\ClassMetadataBuildingContext $metadataBuildingContext)
    {
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : FieldMetadataBuilder
    {
        $this->componentMetadata = $componentMetadata;

        return $this;
    }

    public function withFieldName(string $fieldName) : FieldMetadataBuilder
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function withColumnAnnotation(Annotation\Column $columnAnnotation) : FieldMetadataBuilder
    {
        $this->columnAnnotation = $columnAnnotation;

        return $this;
    }

    public function withIdAnnotation(?Annotation\Id $idAnnotation) : FieldMetadataBuilder
    {
        $this->idAnnotation = $idAnnotation;

        return $this;
    }

    public function withVersionAnnotation(?Annotation\Version $versionAnnotation) : FieldMetadataBuilder
    {
        $this->versionAnnotation = $versionAnnotation;

        return $this;
    }

    public function build() : Mapping\FieldMetadata
    {
        // Validate required fields
        assert($this->componentMetadata !== null);
        assert($this->columnAnnotation !== null);
        assert($this->fieldName !== null);

        $componentClassName = $this->componentMetadata->getClassName();
        $namingStrategy     = $this->metadataBuildingContext->getNamingStrategy();
        $columnName         = $this->columnAnnotation->name
            ?? $namingStrategy->propertyToColumnName($this->fieldName, $componentClassName);
        $fieldMetadata      = new Mapping\FieldMetadata($this->fieldName);

        // For PHP 7.4+, we could potentially infer from property type
        if ($this->columnAnnotation->type === null) {
            throw Mapping\MappingException::propertyTypeIsRequired($componentClassName, $this->fieldName);
        }

        $fieldType    = Type::getType($this->columnAnnotation->type);
        $fieldOptions = $this->columnAnnotation->options ?? [];

        // Check for primary key
        if ($this->idAnnotation !== null) {
            $fieldMetadata->setPrimaryKey(true);

            if ($fieldType->canRequireSQLConversion()) {
                throw Mapping\MappingException::sqlConversionNotAllowedForPrimaryKeyProperties(
                    $componentClassName,
                    $this->fieldName,
                    $fieldType->getName()
                );
            }
        }

        // Check for version
        if ($this->versionAnnotation !== null) {
            // Determine default option for versioned field
            switch ($fieldType->getName()) {
                case 'integer':
                case 'bigint':
                case 'smallint':
                    $fieldOptions = array_merge(['default' => 1], $fieldOptions);
                    break;

                case 'datetime':
                case 'datetime_immutable':
                case 'datetimetz':
                case 'datetimetz_immutable':
                    $fieldOptions = array_merge(['default' => 'CURRENT_TIMESTAMP'], $fieldOptions);
                    break;

                default:
                    if (! isset($fieldOptions['default'])) {
                        throw Mapping\MappingException::unsupportedOptimisticLockingType($fieldType);
                    }

                    break;
            }

            $fieldMetadata->setVersioned(true);
        }

        // Prevent PK and version on same field
        if ($fieldMetadata->isPrimaryKey() && $fieldMetadata->isVersioned()) {
            throw Mapping\MappingException::cannotVersionIdField($componentClassName, $this->fieldName);
        }

        $fieldMetadata->setColumnName($columnName);
        $fieldMetadata->setType($fieldType);
        $fieldMetadata->setOptions($fieldOptions);
        $fieldMetadata->setScale($this->columnAnnotation->scale);
        $fieldMetadata->setPrecision($this->columnAnnotation->precision);
        $fieldMetadata->setNullable($this->columnAnnotation->nullable);
        $fieldMetadata->setUnique($this->columnAnnotation->unique);

        if (! $this->componentMetadata->isMappedSuperclass) {
            $fieldMetadata->setTableName($this->componentMetadata->getTableName());
        }

        if (! empty($this->columnAnnotation->columnDefinition)) {
            $fieldMetadata->setColumnDefinition($this->columnAnnotation->columnDefinition);
        }

        if (! empty($this->columnAnnotation->length)) {
            $fieldMetadata->setLength($this->columnAnnotation->length);
        }

        return $fieldMetadata;
    }
}
