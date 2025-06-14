<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use BackedEnum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ComparatorConfig;
use Doctrine\DBAL\Schema\ForeignKeyConstraintEditor;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Index\IndexedColumn;
use Doctrine\DBAL\Schema\Name\Identifier;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DiscriminatorColumnMapping;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\JoinColumnMapping;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Exception\MissingColumnException;
use Doctrine\ORM\Tools\Exception\NotSupported;
use Throwable;

use function array_diff;
use function array_diff_key;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_map;
use function assert;
use function class_exists;
use function count;
use function current;
use function implode;
use function in_array;
use function is_numeric;
use function method_exists;
use function strtolower;

/**
 * The SchemaTool is a tool to create/drop/update database schemas based on
 * <tt>ClassMetadata</tt> class descriptors.
 *
 * @link    www.doctrine-project.org
 */
class SchemaTool
{
    private const KNOWN_COLUMN_OPTIONS = ['comment', 'unsigned', 'fixed', 'default', 'values'];

    private readonly AbstractPlatform $platform;
    private readonly QuoteStrategy $quoteStrategy;
    private readonly AbstractSchemaManager $schemaManager;

    /**
     * Initializes a new SchemaTool instance that uses the connection of the
     * provided EntityManager.
     */
    public function __construct(private readonly EntityManagerInterface $em)
    {
        $this->platform      = $em->getConnection()->getDatabasePlatform();
        $this->quoteStrategy = $em->getConfiguration()->getQuoteStrategy();
        $this->schemaManager = $em->getConnection()->createSchemaManager();
    }

    /**
     * Creates the database schema for the given array of ClassMetadata instances.
     *
     * @phpstan-param list<ClassMetadata> $classes
     *
     * @throws ToolsException
     */
    public function createSchema(array $classes): void
    {
        $createSchemaSql = $this->getCreateSchemaSql($classes);
        $conn            = $this->em->getConnection();

        foreach ($createSchemaSql as $sql) {
            try {
                $conn->executeStatement($sql);
            } catch (Throwable $e) {
                throw ToolsException::schemaToolFailure($sql, $e);
            }
        }
    }

    /**
     * Gets the list of DDL statements that are required to create the database schema for
     * the given list of ClassMetadata instances.
     *
     * @phpstan-param list<ClassMetadata> $classes
     *
     * @return list<string> The SQL statements needed to create the schema for the classes.
     */
    public function getCreateSchemaSql(array $classes): array
    {
        $schema = $this->getSchemaFromMetadata($classes);

        return $schema->toSql($this->platform);
    }

    /**
     * Detects instances of ClassMetadata that don't need to be processed in the SchemaTool context.
     *
     * @phpstan-param array<string, bool> $processedClasses
     */
    private function processingNotRequired(
        ClassMetadata $class,
        array $processedClasses,
    ): bool {
        return isset($processedClasses[$class->name]) ||
            $class->isMappedSuperclass ||
            $class->isEmbeddedClass ||
            ($class->isInheritanceTypeSingleTable() && $class->name !== $class->rootEntityName) ||
            in_array($class->name, $this->em->getConfiguration()->getSchemaIgnoreClasses());
    }

    /**
     * Resolves fields in index mapping to column names
     *
     * @param mixed[] $indexData index or unique constraint data
     *
     * @return list<string> Column names from combined fields and columns mappings
     */
    private function getIndexColumns(ClassMetadata $class, array $indexData): array
    {
        $columns = [];

        if (
            isset($indexData['columns'], $indexData['fields'])
            || (
                ! isset($indexData['columns'])
                && ! isset($indexData['fields'])
            )
        ) {
            throw MappingException::invalidIndexConfiguration(
                (string) $class,
                $indexData['name'] ?? 'unnamed',
            );
        }

        if (isset($indexData['columns'])) {
            $columns = $indexData['columns'];
        }

        if (isset($indexData['fields'])) {
            foreach ($indexData['fields'] as $fieldName) {
                if ($class->hasField($fieldName)) {
                    $columns[] = $this->quoteStrategy->getColumnName($fieldName, $class, $this->platform);
                } elseif ($class->hasAssociation($fieldName)) {
                    $assoc = $class->getAssociationMapping($fieldName);
                    assert($assoc->isToOneOwningSide());
                    foreach ($assoc->joinColumns as $joinColumn) {
                        $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
                    }
                }
            }
        }

        return $columns;
    }

    /**
     * Creates a Schema instance from a given set of metadata classes.
     *
     * @phpstan-param list<ClassMetadata> $classes
     *
     * @throws NotSupported
     */
    public function getSchemaFromMetadata(array $classes): Schema
    {
        // Reminder for processed classes, used for hierarchies
        $processedClasses     = [];
        $eventManager         = $this->em->getEventManager();
        $metadataSchemaConfig = $this->schemaManager->createSchemaConfig();

        $schema = new Schema([], [], $metadataSchemaConfig);

        $addedFks       = [];
        $blacklistedFks = [];

        foreach ($classes as $class) {
            if ($this->processingNotRequired($class, $processedClasses)) {
                continue;
            }

            $table = $schema->createTable($this->quoteStrategy->getTableName($class, $this->platform));

            if ($class->isInheritanceTypeSingleTable()) {
                $this->gatherColumns($class, $table);
                $this->gatherRelationsSql($class, $table, $schema, $addedFks, $blacklistedFks);

                // Add the discriminator column
                $this->addDiscriminatorColumnDefinition($class, $table);

                // Aggregate all the information from all classes in the hierarchy
                foreach ($class->parentClasses as $parentClassName) {
                    // Parent class information is already contained in this class
                    $processedClasses[$parentClassName] = true;
                }

                foreach ($class->subClasses as $subClassName) {
                    $subClass = $this->em->getClassMetadata($subClassName);
                    $this->gatherColumns($subClass, $table);
                    $this->gatherRelationsSql($subClass, $table, $schema, $addedFks, $blacklistedFks);
                    $processedClasses[$subClassName] = true;
                }
            } elseif ($class->isInheritanceTypeJoined()) {
                // Add all non-inherited fields as columns
                foreach ($class->fieldMappings as $fieldName => $mapping) {
                    if (! isset($mapping->inherited)) {
                        $this->gatherColumn($class, $mapping, $table);
                    }
                }

                $this->gatherRelationsSql($class, $table, $schema, $addedFks, $blacklistedFks);

                // Add the discriminator column only to the root table
                if ($class->name === $class->rootEntityName) {
                    $this->addDiscriminatorColumnDefinition($class, $table);
                } else {
                    // Add an ID FK column to child tables
                    $pkColumns           = [];
                    $inheritedKeyColumns = [];

                    foreach ($class->identifier as $identifierField) {
                        if (isset($class->fieldMappings[$identifierField]->inherited)) {
                            $idMapping = $class->fieldMappings[$identifierField];
                            $this->gatherColumn($class, $idMapping, $table);
                            $columnName = $this->quoteStrategy->getColumnName(
                                $identifierField,
                                $class,
                                $this->platform,
                            );
                            // TODO: This seems rather hackish, can we optimize it?
                            $table->getColumn($columnName)->setAutoincrement(false);

                            $pkColumns[]           = $columnName;
                            $inheritedKeyColumns[] = $columnName;

                            continue;
                        }

                        if (isset($class->associationMappings[$identifierField]->inherited)) {
                            $idMapping = $class->associationMappings[$identifierField];
                            assert($idMapping->isToOneOwningSide());

                            $targetEntity = current(
                                array_filter(
                                    $classes,
                                    static fn (ClassMetadata $class): bool => $class->name === $idMapping->targetEntity,
                                ),
                            );

                            foreach ($idMapping->joinColumns as $joinColumn) {
                                if (isset($targetEntity->fieldMappings[$joinColumn->referencedColumnName])) {
                                    $columnName = $this->quoteStrategy->getJoinColumnName(
                                        $joinColumn,
                                        $class,
                                        $this->platform,
                                    );

                                    $pkColumns[]           = $columnName;
                                    $inheritedKeyColumns[] = $columnName;
                                }
                            }
                        }
                    }

                    if ($inheritedKeyColumns !== []) {
                        // Add a FK constraint on the ID column
                        $table->addForeignKeyConstraint(
                            $this->quoteStrategy->getTableName(
                                $this->em->getClassMetadata($class->rootEntityName),
                                $this->platform,
                            ),
                            $inheritedKeyColumns,
                            $inheritedKeyColumns,
                            ['onDelete' => 'CASCADE'],
                        );
                    }

                    if ($pkColumns !== []) {
                        self::addPrimaryKeyConstraint($table, $pkColumns);
                    }
                }
            } else {
                $this->gatherColumns($class, $table);
                $this->gatherRelationsSql($class, $table, $schema, $addedFks, $blacklistedFks);
            }

            $pkColumns = [];

            foreach ($class->identifier as $identifierField) {
                if (isset($class->fieldMappings[$identifierField])) {
                    $pkColumns[] = $this->quoteStrategy->getColumnName($identifierField, $class, $this->platform);
                } elseif (isset($class->associationMappings[$identifierField])) {
                    $assoc = $class->associationMappings[$identifierField];
                    assert($assoc->isToOneOwningSide());

                    foreach ($assoc->joinColumns as $joinColumn) {
                        $pkColumns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
                    }
                }
            }

            if (! $table->hasIndex('primary')) {
                self::addPrimaryKeyConstraint($table, $pkColumns);
            }

            // there can be unique indexes automatically created for join column
            // if join column is also primary key we should keep only primary key on this column
            // so, remove indexes overruled by primary key
            $primaryKey = $table->getIndex('primary');

            foreach ($table->getIndexes() as $idxKey => $existingIndex) {
                if ($existingIndex !== $primaryKey && $primaryKey->spansColumns(self::getIndexedColumns($existingIndex))) {
                    $table->dropIndex($idxKey);
                }
            }

            if (isset($class->table['indexes'])) {
                foreach ($class->table['indexes'] as $indexName => $indexData) {
                    if (! isset($indexData['flags'])) {
                        $indexData['flags'] = [];
                    }

                    $table->addIndex(
                        $this->getIndexColumns($class, $indexData),
                        is_numeric($indexName) ? null : $indexName,
                        (array) $indexData['flags'],
                        $indexData['options'] ?? [],
                    );
                }
            }

            if (isset($class->table['uniqueConstraints'])) {
                foreach ($class->table['uniqueConstraints'] as $indexName => $indexData) {
                    $uniqIndex = new Index('tmp__' . $indexName, $this->getIndexColumns($class, $indexData), true, false, [], $indexData['options'] ?? []);

                    foreach ($table->getIndexes() as $tableIndexName => $tableIndex) {
                        if ($tableIndex->isFulfilledBy($uniqIndex)) {
                            $table->dropIndex($tableIndexName);
                            break;
                        }
                    }

                    $table->addUniqueIndex(self::getIndexedColumns($uniqIndex), is_numeric($indexName) ? null : $indexName, $indexData['options'] ?? []);
                }
            }

            if (isset($class->table['options'])) {
                foreach ($class->table['options'] as $key => $val) {
                    $table->addOption($key, $val);
                }
            }

            $processedClasses[$class->name] = true;

            if ($class->isIdGeneratorSequence() && $class->name === $class->rootEntityName) {
                $seqDef     = $class->sequenceGeneratorDefinition;
                $quotedName = $this->quoteStrategy->getSequenceName($seqDef, $class, $this->platform);
                if (! $schema->hasSequence($quotedName)) {
                    $schema->createSequence(
                        $quotedName,
                        (int) $seqDef['allocationSize'],
                        (int) $seqDef['initialValue'],
                    );
                }
            }

            if ($eventManager->hasListeners(ToolEvents::postGenerateSchemaTable)) {
                $eventManager->dispatchEvent(
                    ToolEvents::postGenerateSchemaTable,
                    new GenerateSchemaTableEventArgs($class, $schema, $table),
                );
            }
        }

        if ($eventManager->hasListeners(ToolEvents::postGenerateSchema)) {
            $eventManager->dispatchEvent(
                ToolEvents::postGenerateSchema,
                new GenerateSchemaEventArgs($this->em, $schema),
            );
        }

        return $schema;
    }

    /**
     * Gets a portable column definition as required by the DBAL for the discriminator
     * column of a class.
     */
    private function addDiscriminatorColumnDefinition(ClassMetadata $class, Table $table): void
    {
        $discrColumn = $class->discriminatorColumn;
        assert($discrColumn !== null);

        if (strtolower($discrColumn->type) === 'string' && ! isset($discrColumn->length)) {
            $discrColumn->type   = 'string';
            $discrColumn->length = 255;
        }

        $options = [
            'length'    => $discrColumn->length ?? null,
            'notnull'   => true,
        ];

        if (isset($discrColumn->columnDefinition)) {
            $options['columnDefinition'] = $discrColumn->columnDefinition;
        }

        $options = $this->gatherColumnOptions($discrColumn) + $options;
        $table->addColumn($discrColumn->name, $discrColumn->type, $options);
    }

    /**
     * Gathers the column definitions as required by the DBAL of all field mappings
     * found in the given class.
     */
    private function gatherColumns(ClassMetadata $class, Table $table): void
    {
        foreach ($class->fieldMappings as $mapping) {
            if ($class->isInheritanceTypeSingleTable() && isset($mapping->inherited)) {
                continue;
            }

            $this->gatherColumn($class, $mapping, $table);
        }
    }

    /**
     * Creates a column definition as required by the DBAL from an ORM field mapping definition.
     *
     * @param ClassMetadata $class The class that owns the field mapping.
     * @phpstan-param FieldMapping $mapping The field mapping.
     */
    private function gatherColumn(
        ClassMetadata $class,
        FieldMapping $mapping,
        Table $table,
    ): void {
        $columnName = $this->quoteStrategy->getColumnName($mapping->fieldName, $class, $this->platform);
        $columnType = $mapping->type;

        $options            = [];
        $options['length']  = $mapping->length ?? null;
        $options['notnull'] = isset($mapping->nullable) ? ! $mapping->nullable : true;
        if ($class->isInheritanceTypeSingleTable() && $class->parentClasses) {
            $options['notnull'] = false;
        }

        $options['platformOptions']            = [];
        $options['platformOptions']['version'] = $class->isVersioned && $class->versionField === $mapping->fieldName;

        if (strtolower($columnType) === 'string' && $options['length'] === null) {
            $options['length'] = 255;
        }

        if (isset($mapping->precision)) {
            $options['precision'] = $mapping->precision;
        }

        if (isset($mapping->scale)) {
            $options['scale'] = $mapping->scale;
        }

        if (isset($mapping->default)) {
            $options['default'] = $mapping->default;
        }

        if (isset($mapping->columnDefinition)) {
            $options['columnDefinition'] = $mapping->columnDefinition;
        }

        // the 'default' option can be overwritten here
        $options = $this->gatherColumnOptions($mapping) + $options;

        if ($class->isIdGeneratorIdentity() && $class->getIdentifierFieldNames() === [$mapping->fieldName]) {
            $options['autoincrement'] = true;
        }

        if ($class->isInheritanceTypeJoined() && $class->name !== $class->rootEntityName) {
            $options['autoincrement'] = false;
        }

        if ($table->hasColumn($columnName)) {
            // required in some inheritance scenarios
            $table->modifyColumn($columnName, $options);
        } else {
            $table->addColumn($columnName, $columnType, $options);
        }

        $isUnique = $mapping->unique ?? false;
        if ($isUnique) {
            $table->addUniqueIndex([$columnName]);
        }

        $isIndex = $mapping->index ?? false;
        if ($isIndex) {
            $table->addIndex([$columnName]);
        }
    }

    /**
     * Gathers the SQL for properly setting up the relations of the given class.
     * This includes the SQL for foreign key constraints and join tables.
     *
     * @phpstan-param array<string, array{
     *                  foreignTableName: string,
     *                  foreignColumns: list<string>
     *              }>                               $addedFks
     * @phpstan-param array<string, bool>              $blacklistedFks
     *
     * @throws NotSupported
     */
    private function gatherRelationsSql(
        ClassMetadata $class,
        Table $table,
        Schema $schema,
        array &$addedFks,
        array &$blacklistedFks,
    ): void {
        foreach ($class->associationMappings as $id => $mapping) {
            if (isset($mapping->inherited) && ! in_array($id, $class->identifier, true)) {
                continue;
            }

            $foreignClass = $this->em->getClassMetadata($mapping->targetEntity);

            if ($mapping->isToOneOwningSide()) {
                $primaryKeyColumns = []; // PK is unnecessary for this relation-type

                $this->gatherRelationJoinColumns(
                    $mapping->joinColumns,
                    $table,
                    $foreignClass,
                    $mapping,
                    $primaryKeyColumns,
                    $addedFks,
                    $blacklistedFks,
                );
            } elseif ($mapping instanceof ManyToManyOwningSideMapping) {
                // create join table
                $joinTable = $mapping->joinTable;

                $theJoinTable = $schema->createTable(
                    $this->quoteStrategy->getJoinTableName($mapping, $foreignClass, $this->platform),
                );

                foreach ($joinTable->options as $key => $val) {
                    $theJoinTable->addOption($key, $val);
                }

                $primaryKeyColumns = [];

                // Build first FK constraint (relation table => source table)
                $this->gatherRelationJoinColumns(
                    $joinTable->joinColumns,
                    $theJoinTable,
                    $class,
                    $mapping,
                    $primaryKeyColumns,
                    $addedFks,
                    $blacklistedFks,
                );

                // Build second FK constraint (relation table => target table)
                $this->gatherRelationJoinColumns(
                    $joinTable->inverseJoinColumns,
                    $theJoinTable,
                    $foreignClass,
                    $mapping,
                    $primaryKeyColumns,
                    $addedFks,
                    $blacklistedFks,
                );

                self::addPrimaryKeyConstraint($theJoinTable, $primaryKeyColumns);
            }
        }
    }

    /**
     * Gets the class metadata that is responsible for the definition of the referenced column name.
     *
     * Previously this was a simple task, but with DDC-117 this problem is actually recursive. If its
     * not a simple field, go through all identifier field names that are associations recursively and
     * find that referenced column name.
     *
     * TODO: Is there any way to make this code more pleasing?
     *
     * @phpstan-return array{ClassMetadata, string}|null
     */
    private function getDefiningClass(ClassMetadata $class, string $referencedColumnName): array|null
    {
        $referencedFieldName = $class->getFieldName($referencedColumnName);

        if ($class->hasField($referencedFieldName)) {
            return [$class, $referencedFieldName];
        }

        if (in_array($referencedColumnName, $class->getIdentifierColumnNames(), true)) {
            // it seems to be an entity as foreign key
            foreach ($class->getIdentifierFieldNames() as $fieldName) {
                if (
                    $class->hasAssociation($fieldName)
                    && $class->getSingleAssociationJoinColumnName($fieldName) === $referencedColumnName
                ) {
                    return $this->getDefiningClass(
                        $this->em->getClassMetadata($class->associationMappings[$fieldName]->targetEntity),
                        $class->getSingleAssociationReferencedJoinColumnName($fieldName),
                    );
                }
            }
        }

        return null;
    }

    /**
     * Gathers columns and fk constraints that are required for one part of relationship.
     *
     * @phpstan-param list<JoinColumnMapping>          $joinColumns
     * @phpstan-param list<string>                     $primaryKeyColumns
     * @phpstan-param array<string, array{
     *                  foreignTableName: string,
     *                  foreignColumns: list<string>
     *              }>                               $addedFks
     * @phpstan-param array<string,bool>               $blacklistedFks
     *
     * @throws MissingColumnException
     */
    private function gatherRelationJoinColumns(
        array $joinColumns,
        Table $theJoinTable,
        ClassMetadata $class,
        AssociationMapping $mapping,
        array &$primaryKeyColumns,
        array &$addedFks,
        array &$blacklistedFks,
    ): void {
        $localColumns      = [];
        $foreignColumns    = [];
        $fkOptions         = [];
        $foreignTableName  = $this->quoteStrategy->getTableName($class, $this->platform);
        $uniqueConstraints = [];

        foreach ($joinColumns as $joinColumn) {
            [$definingClass, $referencedFieldName] = $this->getDefiningClass(
                $class,
                $joinColumn->referencedColumnName,
            );

            if (! $definingClass) {
                throw MissingColumnException::fromColumnSourceAndTarget(
                    $joinColumn->referencedColumnName,
                    $mapping->sourceEntity,
                    $mapping->targetEntity,
                );
            }

            $quotedColumnName    = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
            $quotedRefColumnName = $this->quoteStrategy->getReferencedJoinColumnName(
                $joinColumn,
                $class,
                $this->platform,
            );

            $primaryKeyColumns[] = $quotedColumnName;
            $localColumns[]      = $quotedColumnName;
            $foreignColumns[]    = $quotedRefColumnName;

            if (! $theJoinTable->hasColumn($quotedColumnName)) {
                // Only add the column to the table if it does not exist already.
                // It might exist already if the foreign key is mapped into a regular
                // property as well.

                $fieldMapping = $definingClass->getFieldMapping($referencedFieldName);

                $columnOptions = ['notnull' => false];

                if (isset($joinColumn->columnDefinition)) {
                    $columnOptions['columnDefinition'] = $joinColumn->columnDefinition;
                } elseif (isset($fieldMapping->columnDefinition)) {
                    $columnOptions['columnDefinition'] = $fieldMapping->columnDefinition;
                }

                if (isset($joinColumn->nullable)) {
                    $columnOptions['notnull'] = ! $joinColumn->nullable;
                }

                $columnOptions += $this->gatherColumnOptions($fieldMapping);

                if (isset($fieldMapping->length)) {
                    $columnOptions['length'] = $fieldMapping->length;
                }

                if ($fieldMapping->type === 'decimal') {
                    $columnOptions['scale']     = $fieldMapping->scale;
                    $columnOptions['precision'] = $fieldMapping->precision;
                }

                $columnOptions = $this->gatherColumnOptions($joinColumn) + $columnOptions;

                $theJoinTable->addColumn($quotedColumnName, $fieldMapping->type, $columnOptions);
            }

            if (isset($joinColumn->unique) && $joinColumn->unique === true) {
                $uniqueConstraints[] = ['columns' => [$quotedColumnName]];
            }

            if (isset($joinColumn->onDelete)) {
                $fkOptions['onDelete'] = $joinColumn->onDelete;
            }

            if (isset($joinColumn->deferrable)) {
                $fkOptions['deferrable'] = $joinColumn->deferrable;
            }
        }

        // Prefer unique constraints over implicit simple indexes created for foreign keys.
        // Also avoids index duplication.
        foreach ($uniqueConstraints as $indexName => $unique) {
            $theJoinTable->addUniqueIndex($unique['columns'], is_numeric($indexName) ? null : $indexName);
        }

        $compositeName = $theJoinTable->getName() . '.' . implode('', $localColumns);
        if (
            isset($addedFks[$compositeName])
            && ($foreignTableName !== $addedFks[$compositeName]['foreignTableName']
            || 0 < count(array_diff($foreignColumns, $addedFks[$compositeName]['foreignColumns'])))
        ) {
            foreach ($theJoinTable->getForeignKeys() as $fkName => $key) {
                if (
                    class_exists(ForeignKeyConstraintEditor::class)
                    && count(array_diff(array_map(static fn (UnqualifiedName $name) => $name->toString(), $key->getReferencingColumnNames()), $localColumns)) === 0
                    && (($key->getReferencedTableName()->toString() !== $foreignTableName)
                    || 0 < count(array_diff(array_map(static fn (UnqualifiedName $name) => $name->toString(), $key->getReferencedColumnNames()), $foreignColumns)))
                ) {
                    $theJoinTable->dropForeignKey($fkName);
                    break;
                }

                if (
                    ! class_exists(ForeignKeyConstraintEditor::class)
                    && count(array_diff($key->getLocalColumns(), $localColumns)) === 0
                    && (($key->getForeignTableName() !== $foreignTableName)
                    || 0 < count(array_diff($key->getForeignColumns(), $foreignColumns)))
                ) {
                    $theJoinTable->removeForeignKey($fkName);
                    break;
                }
            }

            $blacklistedFks[$compositeName] = true;
        } elseif (! isset($blacklistedFks[$compositeName])) {
            $addedFks[$compositeName] = ['foreignTableName' => $foreignTableName, 'foreignColumns' => $foreignColumns];
            $theJoinTable->addForeignKeyConstraint(
                $foreignTableName,
                $localColumns,
                $foreignColumns,
                $fkOptions,
            );
        }
    }

    /** @return mixed[] */
    private function gatherColumnOptions(JoinColumnMapping|FieldMapping|DiscriminatorColumnMapping $mapping): array
    {
        $mappingOptions = $mapping->options ?? [];

        if (isset($mapping->enumType)) {
            $mappingOptions['enumType'] = $mapping->enumType;
        }

        if (($mappingOptions['default'] ?? null) instanceof BackedEnum) {
            $mappingOptions['default'] = $mappingOptions['default']->value;
        }

        if (empty($mappingOptions)) {
            return [];
        }

        $options                    = array_intersect_key($mappingOptions, array_flip(self::KNOWN_COLUMN_OPTIONS));
        $options['platformOptions'] = array_diff_key($mappingOptions, $options);

        return $options;
    }

    /**
     * Drops the database schema for the given classes.
     *
     * In any way when an exception is thrown it is suppressed since drop was
     * issued for all classes of the schema and some probably just don't exist.
     *
     * @phpstan-param list<ClassMetadata> $classes
     */
    public function dropSchema(array $classes): void
    {
        $dropSchemaSql = $this->getDropSchemaSQL($classes);
        $conn          = $this->em->getConnection();

        foreach ($dropSchemaSql as $sql) {
            try {
                $conn->executeStatement($sql);
            } catch (Throwable) {
                // ignored
            }
        }
    }

    /**
     * Drops all elements in the database of the current connection.
     */
    public function dropDatabase(): void
    {
        $dropSchemaSql = $this->getDropDatabaseSQL();
        $conn          = $this->em->getConnection();

        foreach ($dropSchemaSql as $sql) {
            $conn->executeStatement($sql);
        }
    }

    /**
     * Gets the SQL needed to drop the database schema for the connections database.
     *
     * @return list<string>
     */
    public function getDropDatabaseSQL(): array
    {
        return $this->schemaManager
            ->introspectSchema()
            ->toDropSql($this->platform);
    }

    /**
     * Gets SQL to drop the tables defined by the passed classes.
     *
     * @phpstan-param list<ClassMetadata> $classes
     *
     * @return list<string>
     */
    public function getDropSchemaSQL(array $classes): array
    {
        $schema = $this->getSchemaFromMetadata($classes);

        $deployedSchema = $this->schemaManager->introspectSchema();

        foreach ($schema->getTables() as $table) {
            if (! $deployedSchema->hasTable($table->getName())) {
                $schema->dropTable($table->getName());
            }
        }

        if ($this->platform->supportsSequences()) {
            foreach ($schema->getSequences() as $sequence) {
                if (! $deployedSchema->hasSequence($sequence->getName())) {
                    $schema->dropSequence($sequence->getName());
                }
            }

            foreach ($schema->getTables() as $table) {
                if (method_exists($table, 'getPrimaryKeyConstraint')) {
                    $primaryKey = $table->getPrimaryKeyConstraint();
                } else {
                    $primaryKey = $table->getPrimaryKey();
                }

                if ($primaryKey === null) {
                    continue;
                }

                if ($primaryKey instanceof PrimaryKeyConstraint) {
                    $columns = array_map(static fn (UnqualifiedName $name) => $name->toString(), $primaryKey->getColumnNames());
                } else {
                    $columns = self::getIndexedColumns($primaryKey);
                }

                if (count($columns) === 1) {
                    $checkSequence = $table->getName() . '_' . $columns[0] . '_seq';
                    if ($deployedSchema->hasSequence($checkSequence) && ! $schema->hasSequence($checkSequence)) {
                        $schema->createSequence($checkSequence);
                    }
                }
            }
        }

        return $schema->toDropSql($this->platform);
    }

    /**
     * Updates the database schema of the given classes by comparing the ClassMetadata
     * instances to the current database schema that is inspected.
     *
     * @param mixed[] $classes
     */
    public function updateSchema(array $classes): void
    {
        $conn = $this->em->getConnection();

        foreach ($this->getUpdateSchemaSql($classes) as $sql) {
            $conn->executeStatement($sql);
        }
    }

    /**
     * Gets the sequence of SQL statements that need to be performed in order
     * to bring the given class mappings in-synch with the relational schema.
     *
     * @param list<ClassMetadata> $classes The classes to consider.
     *
     * @return list<string> The sequence of SQL statements.
     */
    public function getUpdateSchemaSql(array $classes): array
    {
        $toSchema   = $this->getSchemaFromMetadata($classes);
        $fromSchema = $this->createSchemaForComparison($toSchema);

        if (class_exists(ComparatorConfig::class)) {
            $comparator = $this->schemaManager->createComparator((new ComparatorConfig())->withReportModifiedIndexes(false));
        } else {
            $comparator = $this->schemaManager->createComparator();
        }

        $schemaDiff = $comparator->compareSchemas($fromSchema, $toSchema);

        return $this->platform->getAlterSchemaSQL($schemaDiff);
    }

    /**
     * Creates the schema from the database, ensuring tables from the target schema are whitelisted for comparison.
     */
    private function createSchemaForComparison(Schema $toSchema): Schema
    {
        $connection = $this->em->getConnection();

        // backup schema assets filter
        $config         = $connection->getConfiguration();
        $previousFilter = $config->getSchemaAssetsFilter();

        if ($previousFilter === null) {
            return $this->schemaManager->introspectSchema();
        }

        // whitelist assets we already know about in $toSchema, use the existing filter otherwise
        $config->setSchemaAssetsFilter(static function ($asset) use ($previousFilter, $toSchema): bool {
            $assetName = $asset instanceof AbstractAsset ? $asset->getName() : $asset;

            return $toSchema->hasTable($assetName) || $toSchema->hasSequence($assetName) || $previousFilter($asset);
        });

        try {
            return $this->schemaManager->introspectSchema();
        } finally {
            // restore schema assets filter
            $config->setSchemaAssetsFilter($previousFilter);
        }
    }

    /** @param string[] $primaryKeyColumns */
    private function addPrimaryKeyConstraint(Table $table, array $primaryKeyColumns): void
    {
        if (class_exists(PrimaryKeyConstraint::class)) {
            $primaryKeyColumnNames = [];

            foreach ($primaryKeyColumns as $primaryKeyColumn) {
                $primaryKeyColumnNames[] = new UnqualifiedName(Identifier::unquoted($primaryKeyColumn));
            }

            $table->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, $primaryKeyColumnNames, true));
        } else {
            $table->setPrimaryKey($primaryKeyColumns);
        }
    }

    /** @return string[] */
    private static function getIndexedColumns(Index $index): array
    {
        if (method_exists(Index::class, 'getIndexedColumns')) {
            return array_map(static fn (IndexedColumn $indexedColumn) => $indexedColumn->getColumnName()->toString(), $index->getIndexedColumns());
        }

        return $index->getColumns();
    }
}
