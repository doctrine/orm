<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use function count;

/**
 * A ResultSetMapping describes how a result set of an SQL query maps to a Doctrine result.
 *
 * IMPORTANT NOTE:
 * The properties of this class are only public for fast internal READ access and to (drastically)
 * reduce the size of serialized instances for more effective caching due to better (un-)serialization
 * performance.
 *
 * <b>Users should use the public methods.</b>
 *
 * @todo Think about whether the number of lookup maps can be reduced.
 */
class ResultSetMapping
{
    /**
     * Whether the result is mixed (contains scalar values together with field values).
     *
     * @ignore
     */
    public bool $isMixed = false;

    /**
     * Whether the result is a select statement.
     *
     * @ignore
     */
    public bool $isSelect = true;

    /**
     * Maps alias names to class names.
     *
     * @ignore
     * @var array<string, class-string>
     */
    public array $aliasMap = [];

    /**
     * Maps alias names to related association field names.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public array $relationMap = [];

    /**
     * Maps alias names to parent alias names.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public array $parentAliasMap = [];

    /**
     * Maps column names in the result set to field names for each class.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public array $fieldMappings = [];

    /**
     * Map field names for each class to alias
     *
     * @var array<class-string, array<string, array<string, string>>>
     */
    public array $columnAliasMappings = [];

    /**
     * Maps column names in the result set to the alias/field name to use in the mapped result.
     *
     * @ignore
     * @phpstan-var array<string, string|int>
     */
    public array $scalarMappings = [];

    /**
     * Maps scalar columns to enums
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public $enumMappings = [];

    /**
     * Maps column names in the result set to the alias/field type to use in the mapped result.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public array $typeMappings = [];

    /**
     * Maps entities in the result set to the alias name to use in the mapped result.
     *
     * @ignore
     * @phpstan-var array<string, string|null>
     */
    public array $entityMappings = [];

    /**
     * Maps column names of meta columns (foreign keys, discriminator columns, ...) to field names.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public array $metaMappings = [];

    /**
     * Maps column names in the result set to the alias they belong to.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public array $columnOwnerMap = [];

    /**
     * List of columns in the result set that are used as discriminator columns.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public array $discriminatorColumns = [];

    /**
     * Maps alias names to field names that should be used for indexing.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public array $indexByMap = [];

    /**
     * Map from column names to class names that declare the field the column is mapped to.
     *
     * @ignore
     * @var array<string, class-string>
     */
    public array $declaringClasses = [];

    /**
     * This is necessary to hydrate derivate foreign keys correctly.
     *
     * @phpstan-var array<string, array<string, bool>>
     */
    public array $isIdentifierColumn = [];

    /**
     * Maps column names in the result set to field names for each new object expression.
     *
     * @phpstan-var array<string, array<string, mixed>>
     */
    public array $newObjectMappings = [];

    /**
     * Maps last argument for new objects in order to initiate object construction
     *
     * @phpstan-var array<int|string, array{ownerIndex: string|int, argIndex: int|string}>
     */
    public array $nestedNewObjectArguments = [];

    /**
     * Maps metadata parameter names to the metadata attribute.
     *
     * @phpstan-var array<int|string, string>
     */
    public array $metadataParameterMapping = [];

    /**
     * Contains query parameter names to be resolved as discriminator values
     *
     * @phpstan-var array<string, string>
     */
    public array $discriminatorParameters = [];

    /**
     * Adds an entity result to this ResultSetMapping.
     *
     * @param class-string $class       The class name of the entity.
     * @param string       $alias       The alias for the class. The alias must be unique among all entity
     *                                  results or joined entity results within this ResultSetMapping.
     * @param string|null  $resultAlias The result alias with which the entity result should be
     *                                  placed in the result structure.
     *
     * @return $this
     *
     * @todo Rename: addRootEntity
     */
    public function addEntityResult(string $class, string $alias, string|null $resultAlias = null): static
    {
        $this->aliasMap[$alias]       = $class;
        $this->entityMappings[$alias] = $resultAlias;

        if ($resultAlias !== null) {
            $this->isMixed = true;
        }

        return $this;
    }

    /**
     * Sets a discriminator column for an entity result or joined entity result.
     * The discriminator column will be used to determine the concrete class name to
     * instantiate.
     *
     * @param string $alias       The alias of the entity result or joined entity result the discriminator
     *                            column should be used for.
     * @param string $discrColumn The name of the discriminator column in the SQL result set.
     *
     * @return $this
     *
     * @todo Rename: addDiscriminatorColumn
     */
    public function setDiscriminatorColumn(string $alias, string $discrColumn): static
    {
        $this->discriminatorColumns[$alias] = $discrColumn;
        $this->columnOwnerMap[$discrColumn] = $alias;

        return $this;
    }

    /**
     * Sets a field to use for indexing an entity result or joined entity result.
     *
     * @param string $alias     The alias of an entity result or joined entity result.
     * @param string $fieldName The name of the field to use for indexing.
     *
     * @return $this
     */
    public function addIndexBy(string $alias, string $fieldName): static
    {
        $found = false;

        foreach ([...$this->metaMappings, ...$this->fieldMappings] as $columnName => $columnFieldName) {
            if (! ($columnFieldName === $fieldName && $this->columnOwnerMap[$columnName] === $alias)) {
                continue;
            }

            $this->addIndexByColumn($alias, $columnName);
            $found = true;

            break;
        }

        /* TODO: check if this exception can be put back, for now it's gone because of assumptions made by some ORM internals
        if ( ! $found) {
            $message = sprintf(
                'Cannot add index by for DQL alias %s and field %s without calling addFieldResult() for them before.',
                $alias,
                $fieldName
            );

            throw new \LogicException($message);
        }
        */

        return $this;
    }

    /**
     * Sets to index by a scalar result column name.
     *
     * @return $this
     */
    public function addIndexByScalar(string $resultColumnName): static
    {
        $this->indexByMap['scalars'] = $resultColumnName;

        return $this;
    }

    /**
     * Sets a column to use for indexing an entity or joined entity result by the given alias name.
     *
     * @return $this
     */
    public function addIndexByColumn(string $alias, string $resultColumnName): static
    {
        $this->indexByMap[$alias] = $resultColumnName;

        return $this;
    }

    /**
     * Checks whether an entity result or joined entity result with a given alias has
     * a field set for indexing.
     *
     * @todo Rename: isIndexed($alias)
     */
    public function hasIndexBy(string $alias): bool
    {
        return isset($this->indexByMap[$alias]);
    }

    /**
     * Checks whether the column with the given name is mapped as a field result
     * as part of an entity result or joined entity result.
     *
     * @param string $columnName The name of the column in the SQL result set.
     *
     * @todo Rename: isField
     */
    public function isFieldResult(string $columnName): bool
    {
        return isset($this->fieldMappings[$columnName]);
    }

    /**
     * Adds a field to the result that belongs to an entity or joined entity.
     *
     * @param string            $alias          The alias of the root entity or joined entity to which the field belongs.
     * @param string            $columnName     The name of the column in the SQL result set.
     * @param string            $fieldName      The name of the field on the declaring class.
     * @param class-string|null $declaringClass The name of the class that declares/owns the specified field.
     *                                          When $alias refers to a superclass in a mapped hierarchy but
     *                                          the field $fieldName is defined on a subclass, specify that here.
     *                                          If not specified, the field is assumed to belong to the class
     *                                          designated by $alias.
     *
     * @return $this
     *
     * @todo Rename: addField
     */
    public function addFieldResult(string $alias, string $columnName, string $fieldName, string|null $declaringClass = null): static
    {
        // column name (in result set) => field name
        $this->fieldMappings[$columnName] = $fieldName;
        // column name => alias of owner
        $this->columnOwnerMap[$columnName] = $alias;
        // field name => class name of declaring class
        $declaringClass                      = $declaringClass ?: $this->aliasMap[$alias];
        $this->declaringClasses[$columnName] = $declaringClass;

        $this->columnAliasMappings[$declaringClass][$alias][$fieldName] = $columnName;

        if (! $this->isMixed && $this->scalarMappings) {
            $this->isMixed = true;
        }

        return $this;
    }

    public function hasColumnAliasByField(string $alias, string $fieldName): bool
    {
        $declaringClass = $this->aliasMap[$alias];

        return isset($this->columnAliasMappings[$declaringClass][$alias][$fieldName]);
    }

    public function getColumnAliasByField(string $alias, string $fieldName): string
    {
        $declaringClass = $this->aliasMap[$alias];

        return $this->columnAliasMappings[$declaringClass][$alias][$fieldName];
    }

    /**
     * Adds a joined entity result.
     *
     * @param class-string $class       The class name of the joined entity.
     * @param string       $alias       The unique alias to use for the joined entity.
     * @param string       $parentAlias The alias of the entity result that is the parent of this joined result.
     * @param string       $relation    The association field that connects the parent entity result
     *                                  with the joined entity result.
     *
     * @return $this
     *
     * @todo Rename: addJoinedEntity
     */
    public function addJoinedEntityResult(string $class, string $alias, string $parentAlias, string $relation): static
    {
        $this->aliasMap[$alias]       = $class;
        $this->parentAliasMap[$alias] = $parentAlias;
        $this->relationMap[$alias]    = $relation;

        return $this;
    }

    /**
     * Adds a scalar result mapping.
     *
     * @param string     $columnName The name of the column in the SQL result set.
     * @param string|int $alias      The result alias with which the scalar result should be placed in the result structure.
     * @param string     $type       The column type
     *
     * @return $this
     *
     * @todo Rename: addScalar
     */
    public function addScalarResult(string $columnName, string|int $alias, string $type = 'string'): static
    {
        $this->scalarMappings[$columnName] = $alias;
        $this->typeMappings[$columnName]   = $type;

        if (! $this->isMixed && $this->fieldMappings) {
            $this->isMixed = true;
        }

        return $this;
    }

    /**
     * Adds a scalar result mapping.
     *
     * @param string $columnName The name of the column in the SQL result set.
     * @param string $enumType   The enum type
     *
     * @return $this
     */
    public function addEnumResult(string $columnName, string $enumType): static
    {
        $this->enumMappings[$columnName] = $enumType;

        return $this;
    }

    /**
     * Adds a metadata parameter mappings.
     */
    public function addMetadataParameterMapping(string|int $parameter, string $attribute): void
    {
        $this->metadataParameterMapping[$parameter] = $attribute;
    }

    /**
     * Checks whether a column with a given name is mapped as a scalar result.
     *
     * @todo Rename: isScalar
     */
    public function isScalarResult(string $columnName): bool
    {
        return isset($this->scalarMappings[$columnName]);
    }

    /**
     * Gets the name of the class of an entity result or joined entity result,
     * identified by the given unique alias.
     *
     * @phpstan-return class-string
     */
    public function getClassName(string $alias): string
    {
        return $this->aliasMap[$alias];
    }

    /**
     * Gets the field alias for a column that is mapped as a scalar value.
     *
     * @param string $columnName The name of the column in the SQL result set.
     */
    public function getScalarAlias(string $columnName): string|int
    {
        return $this->scalarMappings[$columnName];
    }

    /**
     * Gets the name of the class that owns a field mapping for the specified column.
     *
     * @phpstan-return class-string
     */
    public function getDeclaringClass(string $columnName): string
    {
        return $this->declaringClasses[$columnName];
    }

    public function getRelation(string $alias): string
    {
        return $this->relationMap[$alias];
    }

    public function isRelation(string $alias): bool
    {
        return isset($this->relationMap[$alias]);
    }

    /**
     * Gets the alias of the class that owns a field mapping for the specified column.
     */
    public function getEntityAlias(string $columnName): string
    {
        return $this->columnOwnerMap[$columnName];
    }

    /**
     * Gets the parent alias of the given alias.
     */
    public function getParentAlias(string $alias): string
    {
        return $this->parentAliasMap[$alias];
    }

    /**
     * Checks whether the given alias has a parent alias.
     */
    public function hasParentAlias(string $alias): bool
    {
        return isset($this->parentAliasMap[$alias]);
    }

    /**
     * Gets the field name for a column name.
     */
    public function getFieldName(string $columnName): string
    {
        return $this->fieldMappings[$columnName];
    }

    /** @return array<string, class-string> */
    public function getAliasMap(): array
    {
        return $this->aliasMap;
    }

    /**
     * Gets the number of different entities that appear in the mapped result.
     *
     * @phpstan-return 0|positive-int
     */
    public function getEntityResultCount(): int
    {
        return count($this->aliasMap);
    }

    /**
     * Checks whether this ResultSetMapping defines a mixed result.
     *
     * Mixed results can only occur in object and array (graph) hydration. In such a
     * case a mixed result means that scalar values are mixed with objects/array in
     * the result.
     */
    public function isMixedResult(): bool
    {
        return $this->isMixed;
    }

    /**
     * Adds a meta column (foreign key or discriminator column) to the result set.
     *
     * @param string      $alias      The result alias with which the meta result should be placed in the result structure.
     * @param string      $columnName The name of the column in the SQL result set.
     * @param string      $fieldName  The name of the field on the declaring class.
     * @param string|null $type       The column type
     *
     * @return $this
     *
     * @todo Make all methods of this class require all parameters and not infer anything
     */
    public function addMetaResult(
        string $alias,
        string $columnName,
        string $fieldName,
        bool $isIdentifierColumn = false,
        string|null $type = null,
    ): static {
        $this->metaMappings[$columnName]   = $fieldName;
        $this->columnOwnerMap[$columnName] = $alias;

        if ($isIdentifierColumn) {
            $this->isIdentifierColumn[$alias][$columnName] = true;
        }

        if ($type) {
            $this->typeMappings[$columnName] = $type;
        }

        return $this;
    }
}
