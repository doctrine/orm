<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use RuntimeException;

use function array_flip;
use function assert;
use function count;
use function trim;

abstract class ToOneAssociationMapping extends AssociationMapping
{
    /** @var array<string, string> */
    public array|null $sourceToTargetKeyColumns = null;

    /** @var array<string, string> */
    public array|null $targetToSourceKeyColumns = null;

    /**
     * @param array<string, mixed> $mappingArray
     * @psalm-param array{
     *     fieldName: string,
     *     sourceEntity: class-string,
     *     targetEntity: class-string,
     *     joinColumns?: mixed[]|null,
     *     isOwningSide: bool, ...} $mappingArray
     */
    public static function fromMappingArray(array $mappingArray): static
    {
        $joinColumns = $mappingArray['joinColumns'] ?? [];

        if (isset($mappingArray['joinColumns'])) {
            unset($mappingArray['joinColumns']);
        }

        $instance = parent::fromMappingArray($mappingArray);

        foreach ($joinColumns as $column) {
            assert($instance->isToOneOwningSide());
            $instance->joinColumns[] = JoinColumnMapping::fromMappingArray($column);
        }

        if ($instance->orphanRemoval) {
            if (! $instance->isCascadeRemove()) {
                $instance->cascade[] = 'remove';
            }

            $instance->unique = null;
        }

        return $instance;
    }

    /**
     * @param mixed[]      $mappingArray
     * @param class-string $name
     * @psalm-param array{
     *     fieldName: string,
     *     sourceEntity: class-string,
     *     targetEntity: class-string,
     *     joinColumns?: mixed[]|null,
     *     isOwningSide: bool, ...} $mappingArray
     */
    public static function fromMappingArrayAndName(
        array $mappingArray,
        NamingStrategy $namingStrategy,
        string $name,
        array|null $table,
        bool $isInheritanceTypeSingleTable,
    ): OneToOneAssociationMapping|ManyToOneAssociationMapping {
        $mapping = static::fromMappingArray($mappingArray);

        if ($mapping->isOwningSide()) {
            assert($mapping instanceof OneToOneOwningSideMapping || $mapping instanceof ManyToOneAssociationMapping);
            if (empty($mapping->joinColumns)) {
                // Apply default join column
                $mapping->joinColumns = [
                    JoinColumnMapping::fromMappingArray([
                        'name' => $namingStrategy->joinColumnName($mapping['fieldName'], $name),
                        'referencedColumnName' => $namingStrategy->referenceColumnName(),
                    ]),
                ];
            }

            $uniqueConstraintColumns = [];

            foreach ($mapping->joinColumns as $joinColumn) {
                if ($mapping->type() === ClassMetadata::ONE_TO_ONE && ! $isInheritanceTypeSingleTable) {
                    assert($mapping instanceof OneToOneAssociationMapping);
                    if (count($mapping->joinColumns) === 1) {
                        if (empty($mapping->id)) {
                            $joinColumn->unique = true;
                        }
                    } else {
                        $uniqueConstraintColumns[] = $joinColumn->name;
                    }
                }

                if (empty($joinColumn->name)) {
                    $joinColumn->name = $namingStrategy->joinColumnName($mapping->fieldName, $name);
                }

                if (empty($joinColumn->referencedColumnName)) {
                    $joinColumn->referencedColumnName = $namingStrategy->referenceColumnName();
                }

                if ($joinColumn->name[0] === '`') {
                    $joinColumn->name   = trim($joinColumn->name, '`');
                    $joinColumn->quoted = true;
                }

                if ($joinColumn->referencedColumnName[0] === '`') {
                    $joinColumn->referencedColumnName = trim($joinColumn->referencedColumnName, '`');
                    $joinColumn->quoted               = true;
                }

                $mapping->sourceToTargetKeyColumns[$joinColumn->name] = $joinColumn->referencedColumnName;
                $mapping->joinColumnFieldNames[$joinColumn->name]     = $joinColumn->fieldName ?? $joinColumn->name;
            }

            if ($uniqueConstraintColumns) {
                if (! $table) {
                    throw new RuntimeException('ClassMetadata::setTable() has to be called before defining a one to one relationship.');
                }

                $table['uniqueConstraints'][$mapping->fieldName . '_uniq'] = ['columns' => $uniqueConstraintColumns];
            }

            $mapping->targetToSourceKeyColumns = array_flip($mapping->sourceToTargetKeyColumns);
        }

        if (isset($mapping->id) && $mapping->id === true && ! $mapping->isOwningSide()) {
            throw MappingException::illegalInverseIdentifierAssociation($name, $mapping->fieldName);
        }

        return $mapping;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === 'joinColumns') {
            assert($this->isToOneOwningSide());
            $joinColumns = [];
            foreach ($value as $column) {
                $joinColumns[] = JoinColumnMapping::fromMappingArray($column);
            }

            $this->joinColumns = $joinColumns;

            return;
        }

        parent::offsetSet($offset, $value);
    }

    /** @return mixed[] */
    public function toArray(): array
    {
        $array = parent::toArray();

        if ($array['joinColumns'] !== []) {
            $joinColumns = [];
            foreach ($array['joinColumns'] as $column) {
                $joinColumns[] = (array) $column;
            }

            $array['joinColumns'] = $joinColumns;
        }

        return $array;
    }

    /** @return list<string> */
    public function __sleep(): array
    {
        $serialized = parent::__sleep();

        if ($this->sourceToTargetKeyColumns !== null) {
            $serialized[] = 'sourceToTargetKeyColumns';
        }

        if ($this->targetToSourceKeyColumns !== null) {
            $serialized[] = 'targetToSourceKeyColumns';
        }

        return $serialized;
    }
}
