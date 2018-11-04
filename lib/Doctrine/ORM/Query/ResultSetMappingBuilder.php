<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use Doctrine\ORM\Utility\PersisterHelper;
use InvalidArgumentException;
use function in_array;
use function sprintf;

/**
 * A ResultSetMappingBuilder uses the EntityManager to automatically populate entity fields.
 */
class ResultSetMappingBuilder extends ResultSetMapping
{
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
     */
    public function addRootEntityFromClassMetadata(
        string $class,
        string $alias,
        array $renamedColumns = [],
        ?int $renameMode = null
    ) {
        $renameMode = $renameMode ?: (empty($renamedColumns) ? $this->defaultRenameMode : self::COLUMN_RENAMING_CUSTOM);

        $this->addEntityResult($class, $alias);
        $this->addAllClassFields($class, $alias, $renamedColumns, $renameMode);
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
     */
    public function addJoinedEntityFromClassMetadata(
        string $class,
        string $alias,
        string $parentAlias,
        string $relation,
        array $renamedColumns = [],
        ?int $renameMode = null
    ) {
        $renameMode = $renameMode ?: (empty($renamedColumns) ? $this->defaultRenameMode : self::COLUMN_RENAMING_CUSTOM);

        $this->addJoinedEntityResult($class, $alias, $parentAlias, $relation);
        $this->addAllClassFields($class, $alias, $renamedColumns, $renameMode);
    }

    /**
     * Adds all fields of the given class to the result set mapping (columns and meta fields).
     *
     * @param string[] $customRenameColumns
     *
     * @throws InvalidArgumentException
     */
    protected function addAllClassFields(string $class, string $alias, array $customRenameColumns, int $renameMode) : void
    {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->em->getClassMetadata($class);
        $platform      = $this->em->getConnection()->getDatabasePlatform();

        if (! $this->isInheritanceSupported($classMetadata)) {
            throw new InvalidArgumentException(
                'ResultSetMapping builder does not currently support your inheritance scheme.'
            );
        }

        foreach ($classMetadata->getDeclaredPropertiesIterator() as $property) {
            switch (true) {
                case $property instanceof FieldMetadata:
                    $columnName  = $property->getColumnName();
                    $columnAlias = $platform->getSQLResultCasing(
                        $this->getColumnAlias($columnName, $renameMode, $customRenameColumns)
                    );

                    if (isset($this->fieldMappings[$columnAlias])) {
                        throw new InvalidArgumentException(
                            sprintf("The column '%s' conflicts with another column in the mapper.", $columnName)
                        );
                    }

                    $this->addFieldResult($alias, $columnAlias, $property->getName());
                    break;

                case $property instanceof ToOneAssociationMetadata && $property->isOwningSide():
                    $targetClass = $this->em->getClassMetadata($property->getTargetEntity());

                    foreach ($property->getJoinColumns() as $joinColumn) {
                        /** @var JoinColumnMetadata $joinColumn */
                        $columnName           = $joinColumn->getColumnName();
                        $referencedColumnName = $joinColumn->getReferencedColumnName();
                        $columnAlias          = $platform->getSQLResultCasing(
                            $this->getColumnAlias($columnName, $renameMode, $customRenameColumns)
                        );

                        if (isset($this->metaMappings[$columnAlias])) {
                            throw new InvalidArgumentException(
                                sprintf("The column '%s' conflicts with another column in the mapper.", $columnName)
                            );
                        }

                        if (! $joinColumn->getType()) {
                            $joinColumn->setType(PersisterHelper::getTypeOfColumn($referencedColumnName, $targetClass, $this->em));
                        }

                        $this->addMetaResult($alias, $columnAlias, $columnName, $property->isPrimaryKey(), $joinColumn->getType());
                    }
                    break;
            }
        }
    }

    /**
     * Checks if inheritance if supported.
     *
     * @return bool
     */
    private function isInheritanceSupported(ClassMetadata $metadata)
    {
        if ($metadata->inheritanceType === InheritanceType::SINGLE_TABLE
            && in_array($metadata->getClassName(), $metadata->discriminatorMap, true)) {
            return true;
        }

        return ! in_array($metadata->inheritanceType, [InheritanceType::SINGLE_TABLE, InheritanceType::JOINED], true);
    }

    /**
     * Gets column alias for a given column.
     *
     * @param string   $columnName
     * @param int      $mode
     * @param string[] $customRenameColumns
     *
     * @return string
     */
    private function getColumnAlias($columnName, $mode, array $customRenameColumns)
    {
        switch ($mode) {
            case self::COLUMN_RENAMING_INCREMENT:
                return $columnName . $this->sqlCounter++;

            case self::COLUMN_RENAMING_CUSTOM:
                return $customRenameColumns[$columnName] ?? $columnName;

            case self::COLUMN_RENAMING_NONE:
                return $columnName;
        }
    }

    /**
     * Generates the Select clause from this ResultSetMappingBuilder.
     *
     * Works only for all the entity results. The select parts for scalar
     * expressions have to be written manually.
     *
     * @param string[] $tableAliases
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
                $field = $this->fieldMappings[$columnName];
                $sql  .= $class->getProperty($field)->getColumnName();
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
