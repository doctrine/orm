<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use function array_merge;
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
     * @var bool
     */
    public $isMixed = false;

    /**
     * Whether the result is a select statement.
     *
     * @ignore
     * @var bool
     */
    public $isSelect = true;

    /**
     * Maps alias names to class names.
     *
     * @ignore
     * @var array<string, class-string>
     */
    public $aliasMap = [];

    /**
     * Maps alias names to related association field names.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public $relationMap = [];

    /**
     * Maps alias names to parent alias names.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public $parentAliasMap = [];

    /**
     * Maps column names in the result set to field names for each class.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public $fieldMappings = [];

    /**
     * Map field names for each class to alias
     *
     * @var array<class-string, array<string, array<string, string>>>
     */
    public $columnAliasMappings = [];

    /**
     * Maps column names in the result set to the alias/field name to use in the mapped result.
     *
     * @ignore
     * @phpstan-var array<string, string|int>
     */
    public $scalarMappings = [];

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
    public $typeMappings = [];

    /**
     * Maps entities in the result set to the alias name to use in the mapped result.
     *
     * @ignore
     * @phpstan-var array<string, string|null>
     */
    public $entityMappings = [];

    /**
     * Maps column names of meta columns (foreign keys, discriminator columns, ...) to field names.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public $metaMappings = [];

    /**
     * Maps column names in the result set to the alias they belong to.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public $columnOwnerMap = [];

    /**
     * List of columns in the result set that are used as discriminator columns.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public $discriminatorColumns = [];

    /**
     * Maps alias names to field names that should be used for indexing.
     *
     * @ignore
     * @phpstan-var array<string, string>
     */
    public $indexByMap = [];

    /**
     * Map from column names to class names that declare the field the column is mapped to.
     *
     * @ignore
     * @var array<string, class-string>
     */
    public $declaringClasses = [];

    /**
     * This is necessary to hydrate derivate foreign keys correctly.
     *
     * @phpstan-var array<string, array<string, bool>>
     */
    public $isIdentifierColumn = [];

    /**
     * Maps column names in the result set to field names for each new object expression.
     *
     * @phpstan-var array<string, array<string, mixed>>
     */
    public $newObjectMappings = [];

    /**
     * Maps metadata parameter names to the metadata attribute.
     *
     * @phpstan-var array<int|string, string>
     */
    public $metadataParameterMapping = [];

    /**
     * Contains query parameter names to be resolved as discriminator values
     *
     * @phpstan-var array<string, string>
     */
    public $discriminatorParameters = [];

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
    public function addEntityResult($class, $alias, $resultAlias = null)
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
    public function setDiscriminatorColumn($alias, $discrColumn)
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
    public function addIndexBy($alias, $fieldName)
    {
        $found = false;

        foreach (array_merge($this->metaMappings, $this->fieldMappings) as $columnName => $columnFieldName) {
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
     * @param string $resultColumnName
     *
     * @return $this
     */
    public function addIndexByScalar($resultColumnName)
    {
        $this->indexByMap['scalars'] = $resultColumnName;

        return $this;
    }

    /**
     * Sets a column to use for indexing an entity or joined entity result by the given alias name.
     *
     * @param string $alias
     * @param string $resultColumnName
     *
     * @return $this
     */
    public function addIndexByColumn($alias, $resultColumnName)
    {
        $this->indexByMap[$alias] = $resultColumnName;

        return $this;
    }

    /**
     * Checks whether an entity result or joined entity result with a given alias has
     * a field set for indexing.
     *
     * @param string $alias
     *
     * @return bool
     *
     * @todo Rename: isIndexed($alias)
     */
    public function hasIndexBy($alias)
    {
        return isset($this->indexByMap[$alias]);
    }

    /**
     * Checks whether the column with the given name is mapped as a field result
     * as part of an entity result or joined entity result.
     *
     * @param string $columnName The name of the column in the SQL result set.
     *
     * @return bool
     *
     * @todo Rename: isField
     */
    public function isFieldResult($columnName)
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
    public function addFieldResult($alias, $columnName, $fieldName, $declaringClass = null)
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
    public function addJoinedEntityResult($class, $alias, $parentAlias, $relation)
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
    public function addScalarResult($columnName, $alias, $type = 'string')
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
    public function addEnumResult($columnName, $enumType)
    {
        $this->enumMappings[$columnName] = $enumType;

        return $this;
    }

    /**
     * Adds a metadata parameter mappings.
     *
     * @param string|int $parameter The parameter name in the SQL result set.
     * @param string     $attribute The metadata attribute.
     *
     * @return void
     */
    public function addMetadataParameterMapping($parameter, $attribute)
    {
        $this->metadataParameterMapping[$parameter] = $attribute;
    }

    /**
     * Checks whether a column with a given name is mapped as a scalar result.
     *
     * @param string $columnName The name of the column in the SQL result set.
     *
     * @return bool
     *
     * @todo Rename: isScalar
     */
    public function isScalarResult($columnName)
    {
        return isset($this->scalarMappings[$columnName]);
    }

    /**
     * Gets the name of the class of an entity result or joined entity result,
     * identified by the given unique alias.
     *
     * @param string $alias
     *
     * @return class-string
     */
    public function getClassName($alias)
    {
        return $this->aliasMap[$alias];
    }

    /**
     * Gets the field alias for a column that is mapped as a scalar value.
     *
     * @param string $columnName The name of the column in the SQL result set.
     *
     * @return string|int
     */
    public function getScalarAlias($columnName)
    {
        return $this->scalarMappings[$columnName];
    }

    /**
     * Gets the name of the class that owns a field mapping for the specified column.
     *
     * @param string $columnName
     *
     * @return class-string
     */
    public function getDeclaringClass($columnName)
    {
        return $this->declaringClasses[$columnName];
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    public function getRelation($alias)
    {
        return $this->relationMap[$alias];
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function isRelation($alias)
    {
        return isset($this->relationMap[$alias]);
    }

    /**
     * Gets the alias of the class that owns a field mapping for the specified column.
     *
     * @param string $columnName
     *
     * @return string
     */
    public function getEntityAlias($columnName)
    {
        return $this->columnOwnerMap[$columnName];
    }

    /**
     * Gets the parent alias of the given alias.
     *
     * @param string $alias
     *
     * @return string
     */
    public function getParentAlias($alias)
    {
        return $this->parentAliasMap[$alias];
    }

    /**
     * Checks whether the given alias has a parent alias.
     *
     * @param string $alias
     *
     * @return bool
     */
    public function hasParentAlias($alias)
    {
        return isset($this->parentAliasMap[$alias]);
    }

    /**
     * Gets the field name for a column name.
     *
     * @param string $columnName
     *
     * @return string
     */
    public function getFieldName($columnName)
    {
        return $this->fieldMappings[$columnName];
    }

    /** @return array<string, class-string> */
    public function getAliasMap()
    {
        return $this->aliasMap;
    }

    /**
     * Gets the number of different entities that appear in the mapped result.
     *
     * @return int
     * @phpstan-return 0|positive-int
     */
    public function getEntityResultCount()
    {
        return count($this->aliasMap);
    }

    /**
     * Checks whether this ResultSetMapping defines a mixed result.
     *
     * Mixed results can only occur in object and array (graph) hydration. In such a
     * case a mixed result means that scalar values are mixed with objects/array in
     * the result.
     *
     * @return bool
     */
    public function isMixedResult()
    {
        return $this->isMixed;
    }

    /**
     * Adds a meta column (foreign key or discriminator column) to the result set.
     *
     * @param string      $alias              The result alias with which the meta result should be placed in the result structure.
     * @param string      $columnName         The name of the column in the SQL result set.
     * @param string      $fieldName          The name of the field on the declaring class.
     * @param bool        $isIdentifierColumn
     * @param string|null $type               The column type
     *
     * @return $this
     *
     * @todo Make all methods of this class require all parameters and not infer anything
     */
    public function addMetaResult($alias, $columnName, $fieldName, $isIdentifierColumn = false, $type = null)
    {
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
