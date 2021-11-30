<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\SQLResultCasing;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Utility\PersisterHelper;
use InvalidArgumentException;

use function explode;
use function in_array;
use function sprintf;
use function strpos;
use function strtolower;

/**
 * A ResultSetMappingBuilder uses the EntityManager to automatically populate entity fields.
 */
class ResultSetMappingBuilder extends ResultSetMapping
{
    use SQLResultCasing;

    /**
     * Picking this rename mode will register entity columns as is,
     * as they are in the database. This can cause clashes when multiple
     * entities are fetched that have columns with the same name.
     */
    public const COLUMN_RENAMING_NONE = 1;

    /**
     * Picking custom renaming allows the user to define the renaming
     * of specific columns with a rename array that contains column names as
     * keys and result alias as values.
     */
    public const COLUMN_RENAMING_CUSTOM = 2;

    /**
     * Incremental renaming uses a result set mapping internal counter to add a
     * number to each column result, leading to uniqueness. This only works if
     * you use {@see generateSelectClause()} to generate the SELECT clause for
     * you.
     */
    public const COLUMN_RENAMING_INCREMENT = 3;

    /** @var int */
    private $sqlCounter = 0;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * Default column renaming mode.
     *
     * @var int
     */
    private $defaultRenameMode;

    /**
     * @param int $defaultRenameMode
     */
    public function __construct(EntityManagerInterface $em, $defaultRenameMode = self::COLUMN_RENAMING_NONE)
    {
        $this->em                = $em;
        $this->defaultRenameMode = $defaultRenameMode;
    }

    /**
     * Adds a root entity and all of its fields to the result set.
     *
     * @param string   $class          The class name of the root entity.
     * @param string   $alias          The unique alias to use for the root entity.
     * @param string[] $renamedColumns Columns that have been renamed (tableColumnName => queryColumnName).
     * @param int|null $renameMode     One of the COLUMN_RENAMING_* constants or array for BC reasons (CUSTOM).
     * @psalm-param array<string, string> $renamedColumns
     *
     * @return void
     */
    public function addRootEntityFromClassMetadata($class, $alias, $renamedColumns = [], $renameMode = null)
    {
        $renameMode     = $renameMode ?: $this->defaultRenameMode;
        $columnAliasMap = $this->getColumnAliasMap($class, $renameMode, $renamedColumns);

        $this->addEntityResult($class, $alias);
        $this->addAllClassFields($class, $alias, $columnAliasMap);
    }

    /**
     * Adds a joined entity and all of its fields to the result set.
     *
     * @param string   $class          The class name of the joined entity.
     * @param string   $alias          The unique alias to use for the joined entity.
     * @param string   $parentAlias    The alias of the entity result that is the parent of this joined result.
     * @param string   $relation       The association field that connects the parent entity result
     *                                 with the joined entity result.
     * @param string[] $renamedColumns Columns that have been renamed (tableColumnName => queryColumnName).
     * @param int|null $renameMode     One of the COLUMN_RENAMING_* constants or array for BC reasons (CUSTOM).
     * @psalm-param array<string, string> $renamedColumns
     *
     * @return void
     */
    public function addJoinedEntityFromClassMetadata($class, $alias, $parentAlias, $relation, $renamedColumns = [], $renameMode = null)
    {
        $renameMode     = $renameMode ?: $this->defaultRenameMode;
        $columnAliasMap = $this->getColumnAliasMap($class, $renameMode, $renamedColumns);

        $this->addJoinedEntityResult($class, $alias, $parentAlias, $relation);
        $this->addAllClassFields($class, $alias, $columnAliasMap);
    }

    /**
     * Adds all fields of the given class to the result set mapping (columns and meta fields).
     *
     * @param string   $class
     * @param string   $alias
     * @param string[] $columnAliasMap
     * @psalm-param array<string, string> $columnAliasMap
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function addAllClassFields($class, $alias, $columnAliasMap = [])
    {
        $classMetadata = $this->em->getClassMetadata($class);
        $platform      = $this->em->getConnection()->getDatabasePlatform();

        if (! $this->isInheritanceSupported($classMetadata)) {
            throw new InvalidArgumentException('ResultSetMapping builder does not currently support your inheritance scheme.');
        }

        foreach ($classMetadata->getColumnNames() as $columnName) {
            $propertyName = $classMetadata->getFieldName($columnName);
            $columnAlias  = $this->getSQLResultCasing($platform, $columnAliasMap[$columnName]);

            if (isset($this->fieldMappings[$columnAlias])) {
                throw new InvalidArgumentException(sprintf(
                    "The column '%s' conflicts with another column in the mapper.",
                    $columnName
                ));
            }

            $this->addFieldResult($alias, $columnAlias, $propertyName);
        }

        foreach ($classMetadata->associationMappings as $associationMapping) {
            if ($associationMapping['isOwningSide'] && $associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                $targetClass  = $this->em->getClassMetadata($associationMapping['targetEntity']);
                $isIdentifier = isset($associationMapping['id']) && $associationMapping['id'] === true;

                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    $columnName  = $joinColumn['name'];
                    $columnAlias = $this->getSQLResultCasing($platform, $columnAliasMap[$columnName]);
                    $columnType  = PersisterHelper::getTypeOfColumn($joinColumn['referencedColumnName'], $targetClass, $this->em);

                    if (isset($this->metaMappings[$columnAlias])) {
                        throw new InvalidArgumentException(sprintf(
                            "The column '%s' conflicts with another column in the mapper.",
                            $columnAlias
                        ));
                    }

                    $this->addMetaResult($alias, $columnAlias, $columnName, $isIdentifier, $columnType);
                }
            }
        }
    }

    private function isInheritanceSupported(ClassMetadataInfo $classMetadata): bool
    {
        if (
            $classMetadata->isInheritanceTypeSingleTable()
            && in_array($classMetadata->name, $classMetadata->discriminatorMap, true)
        ) {
            return true;
        }

        return ! ($classMetadata->isInheritanceTypeSingleTable() || $classMetadata->isInheritanceTypeJoined());
    }

    /**
     * Gets column alias for a given column.
     *
     * @psalm-param array<string, string>  $customRenameColumns
     */
    private function getColumnAlias(string $columnName, int $mode, array $customRenameColumns): string
    {
        switch ($mode) {
            case self::COLUMN_RENAMING_INCREMENT:
                return $columnName . $this->sqlCounter++;

            case self::COLUMN_RENAMING_CUSTOM:
                return $customRenameColumns[$columnName] ?? $columnName;

            case self::COLUMN_RENAMING_NONE:
                return $columnName;

            default:
                throw new InvalidArgumentException(sprintf(
                    '%d is not a valid value for $mode',
                    $mode
                ));
        }
    }

    /**
     * Retrieves a class columns and join columns aliases that are used in the SELECT clause.
     *
     * This depends on the renaming mode selected by the user.
     *
     * @psalm-param class-string $className
     * @psalm-param self::COLUMN_RENAMING_* $mode
     * @psalm-param array<string, string> $customRenameColumns
     *
     * @return string[]
     * @psalm-return array<array-key, string>
     */
    private function getColumnAliasMap(
        string $className,
        int $mode,
        array $customRenameColumns
    ): array {
        if ($customRenameColumns) { // for BC with 2.2-2.3 API
            $mode = self::COLUMN_RENAMING_CUSTOM;
        }

        $columnAlias = [];
        $class       = $this->em->getClassMetadata($className);

        foreach ($class->getColumnNames() as $columnName) {
            $columnAlias[$columnName] = $this->getColumnAlias($columnName, $mode, $customRenameColumns);
        }

        foreach ($class->associationMappings as $associationMapping) {
            if ($associationMapping['isOwningSide'] && $associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    $columnName               = $joinColumn['name'];
                    $columnAlias[$columnName] = $this->getColumnAlias($columnName, $mode, $customRenameColumns);
                }
            }
        }

        return $columnAlias;
    }

    /**
     * Adds the mappings of the results of native SQL queries to the result set.
     *
     * @param mixed[] $queryMapping
     *
     * @return ResultSetMappingBuilder
     */
    public function addNamedNativeQueryMapping(ClassMetadataInfo $class, array $queryMapping)
    {
        if (isset($queryMapping['resultClass'])) {
            return $this->addNamedNativeQueryResultClassMapping($class, $queryMapping['resultClass']);
        }

        return $this->addNamedNativeQueryResultSetMapping($class, $queryMapping['resultSetMapping']);
    }

    /**
     * Adds the class mapping of the results of native SQL queries to the result set.
     *
     * @param string $resultClassName
     *
     * @return $this
     */
    public function addNamedNativeQueryResultClassMapping(ClassMetadataInfo $class, $resultClassName)
    {
        $classMetadata = $this->em->getClassMetadata($resultClassName);
        $shortName     = $classMetadata->reflClass->getShortName();
        $alias         = strtolower($shortName[0]) . '0';

        $this->addEntityResult($class->name, $alias);

        if ($classMetadata->discriminatorColumn) {
            $discrColumn = $classMetadata->discriminatorColumn;

            $this->setDiscriminatorColumn($alias, $discrColumn['name']);
            $this->addMetaResult($alias, $discrColumn['name'], $discrColumn['fieldName'], false, $discrColumn['type']);
        }

        foreach ($classMetadata->getColumnNames() as $key => $columnName) {
            $propertyName = $classMetadata->getFieldName($columnName);

            $this->addFieldResult($alias, $columnName, $propertyName);
        }

        foreach ($classMetadata->associationMappings as $associationMapping) {
            if ($associationMapping['isOwningSide'] && $associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                $targetClass = $this->em->getClassMetadata($associationMapping['targetEntity']);

                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    $columnName = $joinColumn['name'];
                    $columnType = PersisterHelper::getTypeOfColumn($joinColumn['referencedColumnName'], $targetClass, $this->em);

                    $this->addMetaResult($alias, $columnName, $columnName, $classMetadata->isIdentifier($columnName), $columnType);
                }
            }
        }

        return $this;
    }

    /**
     * Adds the result set mapping of the results of native SQL queries to the result set.
     *
     * @param string $resultSetMappingName
     *
     * @return $this
     */
    public function addNamedNativeQueryResultSetMapping(ClassMetadataInfo $class, $resultSetMappingName)
    {
        $counter       = 0;
        $resultMapping = $class->getSqlResultSetMapping($resultSetMappingName);
        $rootShortName = $class->reflClass->getShortName();
        $rootAlias     = strtolower($rootShortName[0]) . $counter;

        if (isset($resultMapping['entities'])) {
            foreach ($resultMapping['entities'] as $key => $entityMapping) {
                $classMetadata = $this->em->getClassMetadata($entityMapping['entityClass']);

                if ($class->reflClass->name === $classMetadata->reflClass->name) {
                    $this->addEntityResult($classMetadata->name, $rootAlias);
                    $this->addNamedNativeQueryEntityResultMapping($classMetadata, $entityMapping, $rootAlias);
                } else {
                    $shortName    = $classMetadata->reflClass->getShortName();
                    $joinAlias    = strtolower($shortName[0]) . ++$counter;
                    $associations = $class->getAssociationsByTargetClass($classMetadata->name);

                    $this->addNamedNativeQueryEntityResultMapping($classMetadata, $entityMapping, $joinAlias);

                    foreach ($associations as $relation => $mapping) {
                        $this->addJoinedEntityResult($mapping['targetEntity'], $joinAlias, $rootAlias, $relation);
                    }
                }
            }
        }

        if (isset($resultMapping['columns'])) {
            foreach ($resultMapping['columns'] as $entityMapping) {
                $type = isset($class->fieldNames[$entityMapping['name']])
                    ? PersisterHelper::getTypeOfColumn($entityMapping['name'], $class, $this->em)
                    : 'string';

                $this->addScalarResult($entityMapping['name'], $entityMapping['name'], $type);
            }
        }

        return $this;
    }

    /**
     * Adds the entity result mapping of the results of native SQL queries to the result set.
     *
     * @param mixed[] $entityMapping
     * @param string  $alias
     *
     * @return $this
     *
     * @throws MappingException
     * @throws InvalidArgumentException
     */
    public function addNamedNativeQueryEntityResultMapping(ClassMetadataInfo $classMetadata, array $entityMapping, $alias)
    {
        if (isset($entityMapping['discriminatorColumn']) && $entityMapping['discriminatorColumn']) {
            $discriminatorColumn = $entityMapping['discriminatorColumn'];
            $discriminatorType   = $classMetadata->getDiscriminatorColumn()['type'];

            $this->setDiscriminatorColumn($alias, $discriminatorColumn);
            $this->addMetaResult($alias, $discriminatorColumn, $discriminatorColumn, false, $discriminatorType);
        }

        if (isset($entityMapping['fields']) && ! empty($entityMapping['fields'])) {
            foreach ($entityMapping['fields'] as $field) {
                $fieldName = $field['name'];
                $relation  = null;

                if (strpos($fieldName, '.') !== false) {
                    [$relation, $fieldName] = explode('.', $fieldName);
                }

                if (isset($classMetadata->associationMappings[$relation])) {
                    if ($relation) {
                        $associationMapping = $classMetadata->associationMappings[$relation];
                        $joinAlias          = $alias . $relation;
                        $parentAlias        = $alias;

                        $this->addJoinedEntityResult($associationMapping['targetEntity'], $joinAlias, $parentAlias, $relation);
                        $this->addFieldResult($joinAlias, $field['column'], $fieldName);
                    } else {
                        $this->addFieldResult($alias, $field['column'], $fieldName, $classMetadata->name);
                    }
                } else {
                    if (! isset($classMetadata->fieldMappings[$fieldName])) {
                        throw new InvalidArgumentException("Entity '" . $classMetadata->name . "' has no field '" . $fieldName . "'. ");
                    }

                    $this->addFieldResult($alias, $field['column'], $fieldName, $classMetadata->name);
                }
            }
        } else {
            foreach ($classMetadata->getColumnNames() as $columnName) {
                $propertyName = $classMetadata->getFieldName($columnName);

                $this->addFieldResult($alias, $columnName, $propertyName);
            }
        }

        return $this;
    }

    /**
     * Generates the Select clause from this ResultSetMappingBuilder.
     *
     * Works only for all the entity results. The select parts for scalar
     * expressions have to be written manually.
     *
     * @param string[] $tableAliases
     * @psalm-param array<string, string> $tableAliases
     *
     * @return string
     */
    public function generateSelectClause($tableAliases = [])
    {
        $sql = '';

        foreach ($this->columnOwnerMap as $columnName => $dqlAlias) {
            $tableAlias = $tableAliases[$dqlAlias] ?? $dqlAlias;

            if ($sql) {
                $sql .= ', ';
            }

            $sql .= $tableAlias . '.';

            if (isset($this->fieldMappings[$columnName])) {
                $class = $this->em->getClassMetadata($this->declaringClasses[$columnName]);
                $sql  .= $class->fieldMappings[$this->fieldMappings[$columnName]]['columnName'];
            } elseif (isset($this->metaMappings[$columnName])) {
                $sql .= $this->metaMappings[$columnName];
            } elseif (isset($this->discriminatorColumns[$dqlAlias])) {
                $sql .= $this->discriminatorColumns[$dqlAlias];
            }

            $sql .= ' AS ' . $columnName;
        }

        return $sql;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->generateSelectClause([]);
    }
}
