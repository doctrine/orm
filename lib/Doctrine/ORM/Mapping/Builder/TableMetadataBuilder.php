<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function assert;

class TableMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var Mapping\ClassMetadata */
    private $entityClassMetadata;

    /** @var Annotation\Table */
    private $tableAnnotation;

    public function __construct(Mapping\ClassMetadataBuildingContext $metadataBuildingContext)
    {
        $this->metadataBuildingContext = $metadataBuildingContext;

        return $this;
    }

    public function withEntityClassMetadata(Mapping\ClassMetadata $entityClassMetadata) : TableMetadataBuilder
    {
        $this->entityClassMetadata = $entityClassMetadata;

        return $this;
    }

    public function withTableAnnotation(Annotation\Table $tableAnnotation) : TableMetadataBuilder
    {
        $this->tableAnnotation = $tableAnnotation;

        return $this;
    }

    public function build() : Mapping\TableMetadata
    {
        // Validate required fields
        assert($this->entityClassMetadata !== null);

        $namingStrategy = $this->metadataBuildingContext->getNamingStrategy();
        $tableMetadata  = new Mapping\TableMetadata();

        $tableMetadata->setName($namingStrategy->classToTableName($this->entityClassMetadata->getClassName()));

        if ($this->tableAnnotation === null) {
            return $tableMetadata;
        }

        if (! empty($this->tableAnnotation->name)) {
            $tableMetadata->setName($this->tableAnnotation->name);
        }

        if (! empty($this->tableAnnotation->schema)) {
            $tableMetadata->setSchema($this->tableAnnotation->schema);
        }

        foreach ($this->tableAnnotation->options as $optionName => $optionValue) {
            $tableMetadata->addOption($optionName, $optionValue);
        }

        /** @var Annotation\Index $indexAnnotation */
        foreach ($this->tableAnnotation->indexes as $indexAnnotation) {
            $tableMetadata->addIndex([
                'name'    => $indexAnnotation->name,
                'columns' => $indexAnnotation->columns,
                'unique'  => $indexAnnotation->unique,
                'options' => $indexAnnotation->options,
                'flags'   => $indexAnnotation->flags,
            ]);
        }

        /** @var Annotation\UniqueConstraint $uniqueConstraintAnnotation */
        foreach ($this->tableAnnotation->uniqueConstraints as $uniqueConstraintAnnotation) {
            $tableMetadata->addUniqueConstraint([
                'name'    => $uniqueConstraintAnnotation->name,
                'columns' => $uniqueConstraintAnnotation->columns,
                'options' => $uniqueConstraintAnnotation->options,
                'flags'   => $uniqueConstraintAnnotation->flags,
            ]);
        }

        return $tableMetadata;
    }
}