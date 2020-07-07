<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Visitor\DropSchemaSqlCollector;
use Doctrine\DBAL\Schema\Visitor\RemoveNamespacedAssets;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\EmbeddedMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\GeneratorType;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Exception\MissingColumnException;
use Doctrine\ORM\Tools\Exception\NotSupported;
use Throwable;
use function array_diff;
use function array_diff_key;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function count;
use function implode;
use function in_array;
use function is_int;
use function is_numeric;
use function reset;
use function sprintf;
use function strtolower;

/**
 * The SchemaTool is a tool to create/drop/update database schemas based on
 * <tt>ClassMetadata</tt> class descriptors.
 */
class SchemaTool
{
    private const KNOWN_COLUMN_OPTIONS = ['comment', 'unsigned', 'fixed', 'default'];

    /** @var EntityManagerInterface */
    private $em;

    /** @var AbstractPlatform */
    private $platform;

    /**
     * Initializes a new SchemaTool instance that uses the connection of the
     * provided EntityManager.
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em       = $em;
        $this->platform = $em->getConnection()->getDatabasePlatform();
    }

    /**
     * Creates the database schema for the given array of ClassMetadata instances.
     *
     * @param ClassMetadata[] $classes
     *
     * @throws ToolsException
     */
    public function createSchema(array $classes)
    {
        $createSchemaSql = $this->getCreateSchemaSql($classes);
        $conn            = $this->em->getConnection();

        foreach ($createSchemaSql as $sql) {
            try {
                $conn->executeQuery($sql);
            } catch (Throwable $e) {
                throw ToolsException::schemaToolFailure($sql, $e);
            }
        }
    }

    /**
     * Gets the list of DDL statements that are required to create the database schema for
     * the given list of ClassMetadata instances.
     *
     * @param ClassMetadata[] $classes
     *
     * @return string[] The SQL statements needed to create the schema for the classes.
     */
    public function getCreateSchemaSql(array $classes)
    {
        $schema = $this->getSchemaFromMetadata($classes);

        return $schema->toSql($this->platform);
    }

    /**
     * Detects instances of ClassMetadata that don't need to be processed in the SchemaTool context.
     *
     * @param ClassMetadata   $class
     * @param ClassMetadata[] $processedClasses
     *
     * @return bool
     */
    private function processingNotRequired($class, array $processedClasses)
    {
        return isset($processedClasses[$class->getClassName()]) ||
            $class->isMappedSuperclass ||
            $class->isEmbeddedClass ||
            ($class->inheritanceType === InheritanceType::SINGLE_TABLE && ! $class->isRootEntity() ||
            in_array($class->getClassName(), $this->em->getConfiguration()->getSchemaIgnoreClasses()));
    }

    /**
     * Creates a Schema instance from a given set of metadata classes.
     *
     * @param ClassMetadata[] $classes
     *
     * @return Schema
     *
     * @throws ORMException
     */
    public function getSchemaFromMetadata(array $classes)
    {
        // Reminder for processed classes, used for hierarchies
        $processedClasses     = [];
        $eventManager         = $this->em->getEventManager();
        $schemaManager        = $this->em->getConnection()->getSchemaManager();
        $metadataSchemaConfig = $schemaManager->createSchemaConfig();

        $metadataSchemaConfig->setExplicitForeignKeyIndexes(false);
        $schema = new Schema([], [], $metadataSchemaConfig);

        $addedFks       = [];
        $blacklistedFks = [];

        foreach ($classes as $class) {
            /** @var ClassMetadata $class */
            if ($this->processingNotRequired($class, $processedClasses)) {
                continue;
            }

            $table = $schema->createTable($class->table->getQuotedQualifiedName($this->platform));

            switch ($class->inheritanceType) {
                case InheritanceType::SINGLE_TABLE:
                    $this->gatherColumns($class, $table);
                    $this->gatherRelationsSql($class, $table, $schema, $addedFks, $blacklistedFks);

                    // Add the discriminator column
                    $this->addDiscriminatorColumnDefinition($class, $table);

                    // Aggregate all the information from all classes in the hierarchy
                    $parentClass = $class;

                    while (($parentClass = $parentClass->getParent()) !== null) {
                        // Parent class information is already contained in this class
                        $processedClasses[$parentClass->getClassName()] = true;
                    }

                    foreach ($class->getSubClasses() as $subClassName) {
                        $subClass = $this->em->getClassMetadata($subClassName);

                        $this->gatherColumns($subClass, $table);
                        $this->gatherRelationsSql($subClass, $table, $schema, $addedFks, $blacklistedFks);

                        $processedClasses[$subClassName] = true;
                    }

                    break;

                case InheritanceType::JOINED:
                    // Add all non-inherited fields as columns
                    $pkColumns = [];

                    foreach ($class->getPropertiesIterator() as $fieldName => $property) {
                        if (! ($property instanceof FieldMetadata)) {
                            continue;
                        }

                        if (! $class->isInheritedProperty($fieldName)) {
                            $columnName = $this->platform->quoteIdentifier($property->getColumnName());

                            $this->gatherColumn($class, $property, $table);

                            if ($class->isIdentifier($fieldName)) {
                                $pkColumns[] = $columnName;
                            }
                        }
                    }

                    $this->gatherRelationsSql($class, $table, $schema, $addedFks, $blacklistedFks);

                    // Add the discriminator column only to the root table
                    if ($class->isRootEntity()) {
                        $this->addDiscriminatorColumnDefinition($class, $table);
                    } else {
                        // Add an ID FK column to child tables
                        $inheritedKeyColumns = [];

                        foreach ($class->identifier as $identifierField) {
                            $idProperty = $class->getProperty($identifierField);

                            if ($class->isInheritedProperty($identifierField)) {
                                $column     = $this->gatherColumn($class, $idProperty, $table);
                                $columnName = $column->getQuotedName($this->platform);

                                // TODO: This seems rather hackish, can we optimize it?
                                $column->setAutoincrement(false);

                                $pkColumns[]           = $columnName;
                                $inheritedKeyColumns[] = $columnName;
                            }
                        }

                        if (! empty($inheritedKeyColumns)) {
                            // Add a FK constraint on the ID column
                            $rootClass = $this->em->getClassMetadata($class->getRootClassName());

                            $table->addForeignKeyConstraint(
                                $rootClass->table->getQuotedQualifiedName($this->platform),
                                $inheritedKeyColumns,
                                $inheritedKeyColumns,
                                ['onDelete' => 'CASCADE']
                            );
                        }
                    }

                    $table->setPrimaryKey($pkColumns);

                    break;

                case InheritanceType::TABLE_PER_CLASS:
                    throw NotSupported::create();

                default:
                    $this->gatherColumns($class, $table);
                    $this->gatherRelationsSql($class, $table, $schema, $addedFks, $blacklistedFks);

                    break;
            }

            $pkColumns = [];

            foreach ($class->identifier as $identifierField) {
                $property = $class->getProperty($identifierField);

                if ($property instanceof FieldMetadata) {
                    $pkColumns[] = $this->platform->quoteIdentifier($property->getColumnName());

                    continue;
                }

                if ($property instanceof ToOneAssociationMetadata) {
                    foreach ($property->getJoinColumns() as $joinColumn) {
                        $pkColumns[] = $this->platform->quoteIdentifier($joinColumn->getColumnName());
                    }
                }
            }

            if (! $table->hasIndex('primary')) {
                $table->setPrimaryKey($pkColumns);
            }

            // there can be unique indexes automatically created for join column
            // if join column is also primary key we should keep only primary key on this column
            // so, remove indexes overruled by primary key
            $primaryKey = $table->getIndex('primary');

            foreach ($table->getIndexes() as $idxKey => $existingIndex) {
                if ($primaryKey->overrules($existingIndex)) {
                    $table->dropIndex($idxKey);
                }
            }

            if ($class->table->getIndexes()) {
                foreach ($class->table->getIndexes() as $indexName => $indexData) {
                    $indexName = is_numeric($indexName) ? null : $indexName;
                    $index     = new Index($indexName, $indexData['columns'], $indexData['unique'], false, $indexData['flags'], $indexData['options']);

                    foreach ($table->getIndexes() as $tableIndexName => $tableIndex) {
                        if ($tableIndex->isFullfilledBy($index)) {
                            $table->dropIndex($tableIndexName);
                            break;
                        }
                    }

                    if ($indexData['unique']) {
                        $table->addUniqueIndex($indexData['columns'], $indexName, $indexData['options']);
                    } else {
                        $table->addIndex($indexData['columns'], $indexName, $indexData['flags'], $indexData['options']);
                    }
                }
            }

            if ($class->table->getUniqueConstraints()) {
                foreach ($class->table->getUniqueConstraints() as $indexName => $indexData) {
                    $indexName = is_numeric($indexName) ? null : $indexName;
                    $uniqIndex = new Index($indexName, $indexData['columns'], true, false, $indexData['flags'], $indexData['options']);

                    foreach ($table->getIndexes() as $tableIndexName => $tableIndex) {
                        if ($tableIndex->isFullfilledBy($uniqIndex)) {
                            $table->dropIndex($tableIndexName);
                            break;
                        }
                    }

                    $table->addUniqueConstraint($indexData['columns'], $indexName, $indexData['flags'], $indexData['options']);
                }
            }

            if ($class->table->getOptions()) {
                foreach ($class->table->getOptions() as $key => $val) {
                    $table->addOption($key, $val);
                }
            }

            $processedClasses[$class->getClassName()] = true;

            foreach ($class->getPropertiesIterator() as $property) {
                if (! $property instanceof FieldMetadata
                    || ! $property->hasValueGenerator()
                    || $property->getValueGenerator()->getType() !== GeneratorType::SEQUENCE
                    || $class->getClassName() !== $class->getRootClassName()) {
                    continue;
                }

                $generator  = $property->getValueGenerator()->getGenerator();
                $quotedName = $generator->getSequenceName();

                if (! $schema->hasSequence($quotedName)) {
                    $schema->createSequence($quotedName, $generator->getAllocationSize());
                }
            }

            if ($eventManager->hasListeners(ToolEvents::postGenerateSchemaTable)) {
                $eventManager->dispatchEvent(
                    ToolEvents::postGenerateSchemaTable,
                    new GenerateSchemaTableEventArgs($class, $schema, $table)
                );
            }
        }

        if (! $this->platform->supportsSchemas() && ! $this->platform->canEmulateSchemas()) {
            $schema->visit(new RemoveNamespacedAssets());
        }

        if ($eventManager->hasListeners(ToolEvents::postGenerateSchema)) {
            $eventManager->dispatchEvent(
                ToolEvents::postGenerateSchema,
                new GenerateSchemaEventArgs($this->em, $schema)
            );
        }

        return $schema;
    }

    /**
     * Gets a portable column definition as required by the DBAL for the discriminator
     * column of a class.
     *
     * @param ClassMetadata $class
     */
    private function addDiscriminatorColumnDefinition($class, Table $table)
    {
        $discrColumn     = $class->discriminatorColumn;
        $discrColumnType = $discrColumn->getTypeName();
        $options         = [
            'notnull' => ! $discrColumn->isNullable(),
        ];

        switch ($discrColumnType) {
            case 'string':
                $options['length'] = $discrColumn->getLength() ?? 255;
                break;

            case 'decimal':
                $options['scale']     = $discrColumn->getScale();
                $options['precision'] = $discrColumn->getPrecision();
                break;
        }

        if (! empty($discrColumn->getColumnDefinition())) {
            $options['columnDefinition'] = $discrColumn->getColumnDefinition();
        }

        $table->addColumn($discrColumn->getColumnName(), $discrColumnType, $options);
    }

    /**
     * Gathers the column definitions as required by the DBAL of all field mappings
     * found in the given class.
     */
    private function gatherColumns(ClassMetadata $class, Table $table, ?string $columnPrefix = null)
    {
        $pkColumns = [];

        foreach ($class->getPropertiesIterator() as $fieldName => $property) {
            if ($class->inheritanceType === InheritanceType::SINGLE_TABLE && $class->isInheritedProperty($fieldName)) {
                continue;
            }

            switch (true) {
                case $property instanceof FieldMetadata:
                    $this->gatherColumn($class, $property, $table, $columnPrefix);

                    if ($property->isPrimaryKey()) {
                        $pkColumns[] = $this->platform->quoteIdentifier($property->getColumnName());
                    }

                    break;

                case $property instanceof EmbeddedMetadata:
                    $foreignClass = $this->em->getClassMetadata($property->getTargetEntity());

                    $this->gatherColumns($foreignClass, $table, $property->getColumnPrefix());

                    break;
            }
        }
    }

    /**
     * Creates a column definition as required by the DBAL from an ORM field mapping definition.
     *
     * @return Column The portable column definition as required by the DBAL.
     */
    private function gatherColumn(
        ClassMetadata $classMetadata,
        FieldMetadata $fieldMetadata,
        Table $table,
        ?string $columnPrefix = null
    ) {
        $fieldName  = $fieldMetadata->getName();
        $columnName = sprintf('%s%s', $columnPrefix, $fieldMetadata->getColumnName());
        $columnType = $fieldMetadata->getTypeName();

        $options = [
            'length'          => $fieldMetadata->getLength(),
            'notnull'         => ! $fieldMetadata->isNullable(),
            'platformOptions' => [
                'version' => ($classMetadata->isVersioned() && $classMetadata->versionProperty->getName() === $fieldName),
            ],
        ];

        if ($classMetadata->inheritanceType === InheritanceType::SINGLE_TABLE && $classMetadata->getParent()) {
            $options['notnull'] = false;
        }

        if (strtolower($columnType) === 'string' && $options['length'] === null) {
            $options['length'] = 255;
        }

        if (is_int($fieldMetadata->getPrecision())) {
            $options['precision'] = $fieldMetadata->getPrecision();
        }

        if (is_int($fieldMetadata->getScale())) {
            $options['scale'] = $fieldMetadata->getScale();
        }

        if ($fieldMetadata->getColumnDefinition()) {
            $options['columnDefinition'] = $fieldMetadata->getColumnDefinition();
        }

        $fieldOptions = $fieldMetadata->getOptions();

        // the 'default' option can be overwritten here
        $options = $this->gatherColumnOptions($fieldOptions) + $options;

        if ($fieldMetadata->hasValueGenerator() && $fieldMetadata->getValueGenerator()->getType() === GeneratorType::IDENTITY && $classMetadata->getIdentifierFieldNames() === [$fieldName]) {
            $options['autoincrement'] = true;
        }

        if ($classMetadata->inheritanceType === InheritanceType::JOINED && ! $classMetadata->isRootEntity()) {
            $options['autoincrement'] = false;
        }

        $quotedColumnName = $this->platform->quoteIdentifier($columnName);

        if ($table->hasColumn($quotedColumnName)) {
            // required in some inheritance scenarios
            $table->changeColumn($quotedColumnName, $options);

            $column = $table->getColumn($quotedColumnName);
        } else {
            $column = $table->addColumn($quotedColumnName, $columnType, $options);
        }

        if ($fieldMetadata->isUnique()) {
            $table->addUniqueIndex([$columnName]);
        }

        return $column;
    }

    /**
     * Gathers the SQL for properly setting up the relations of the given class.
     * This includes the SQL for foreign key constraints and join tables.
     *
     * @param ClassMetadata $class
     * @param Table         $table
     * @param Schema        $schema
     * @param mixed[][]     $addedFks
     * @param bool[]        $blacklistedFks
     *
     * @throws ORMException
     */
    private function gatherRelationsSql($class, $table, $schema, &$addedFks, &$blacklistedFks)
    {
        foreach ($class->getPropertiesIterator() as $fieldName => $property) {
            if (! ($property instanceof AssociationMetadata)) {
                continue;
            }

            if ($class->isInheritedProperty($fieldName) && ! $property->getDeclaringClass()->isMappedSuperclass) {
                continue;
            }

            if (! $property->isOwningSide()) {
                continue;
            }

            $foreignClass = $this->em->getClassMetadata($property->getTargetEntity());

            switch (true) {
                case $property instanceof ToOneAssociationMetadata:
                    $primaryKeyColumns = []; // PK is unnecessary for this relation-type

                    $this->gatherRelationJoinColumns(
                        $property->getJoinColumns(),
                        $table,
                        $foreignClass,
                        $property,
                        $primaryKeyColumns,
                        $addedFks,
                        $blacklistedFks
                    );

                    break;

                case $property instanceof OneToManyAssociationMetadata:
                    //... create join table, one-many through join table supported later
                    throw NotSupported::create();

                case $property instanceof ManyToManyAssociationMetadata:
                    // create join table
                    $joinTable     = $property->getJoinTable();
                    $joinTableName = $joinTable->getQuotedQualifiedName($this->platform);
                    $theJoinTable  = $schema->createTable($joinTableName);

                    $primaryKeyColumns = [];

                    // Build first FK constraint (relation table => source table)
                    $this->gatherRelationJoinColumns(
                        $joinTable->getJoinColumns(),
                        $theJoinTable,
                        $class,
                        $property,
                        $primaryKeyColumns,
                        $addedFks,
                        $blacklistedFks
                    );

                    // Build second FK constraint (relation table => target table)
                    $this->gatherRelationJoinColumns(
                        $joinTable->getInverseJoinColumns(),
                        $theJoinTable,
                        $foreignClass,
                        $property,
                        $primaryKeyColumns,
                        $addedFks,
                        $blacklistedFks
                    );

                    $theJoinTable->setPrimaryKey($primaryKeyColumns);

                    break;
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
     * @param ClassMetadata $class
     * @param string        $referencedColumnName
     *
     * @return mixed[] (ClassMetadata, referencedFieldName)
     */
    private function getDefiningClass($class, $referencedColumnName)
    {
        if (isset($class->fieldNames[$referencedColumnName])) {
            $propertyName = $class->fieldNames[$referencedColumnName];

            if ($class->hasField($propertyName)) {
                return [$class, $propertyName];
            }
        }

        $idColumns        = $class->getIdentifierColumns($this->em);
        $idColumnNameList = array_keys($idColumns);

        if (! in_array($referencedColumnName, $idColumnNameList, true)) {
            return null;
        }

        // it seems to be an entity as foreign key
        foreach ($class->getIdentifierFieldNames() as $fieldName) {
            $property = $class->getProperty($fieldName);

            if (! ($property instanceof AssociationMetadata)) {
                continue;
            }

            $joinColumns = $property->getJoinColumns();

            if (count($joinColumns) > 1) {
                throw MappingException::noSingleAssociationJoinColumnFound($class->getClassName(), $fieldName);
            }

            $joinColumn = reset($joinColumns);

            if ($joinColumn->getColumnName() === $referencedColumnName) {
                $targetEntity = $this->em->getClassMetadata($property->getTargetEntity());

                return $this->getDefiningClass($targetEntity, $joinColumn->getReferencedColumnName());
            }
        }

        return null;
    }

    /**
     * Gathers columns and fk constraints that are required for one part of relationship.
     *
     * @param JoinColumnMetadata[] $joinColumns
     * @param Table                $theJoinTable
     * @param ClassMetadata        $class
     * @param AssociationMetadata  $mapping
     * @param string[]             $primaryKeyColumns
     * @param mixed[][]            $addedFks
     * @param bool[]               $blacklistedFks
     *
     * @throws ORMException
     */
    private function gatherRelationJoinColumns(
        $joinColumns,
        $theJoinTable,
        $class,
        $mapping,
        &$primaryKeyColumns,
        &$addedFks,
        &$blacklistedFks
    ) {
        $localColumns      = [];
        $foreignColumns    = [];
        $fkOptions         = [];
        $foreignTableName  = $class->table->getQuotedQualifiedName($this->platform);
        $uniqueConstraints = [];

        foreach ($joinColumns as $joinColumn) {
            [$definingClass, $referencedFieldName] = $this->getDefiningClass(
                $class,
                $joinColumn->getReferencedColumnName()
            );

            if (! $definingClass) {
                throw MissingColumnException::fromColumnSourceAndTarget(
                    $joinColumn->getReferencedColumnName(),
                    $mapping->getSourceEntity(),
                    $mapping->getTargetEntity()
                );
            }

            $quotedColumnName           = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $quotedReferencedColumnName = $this->platform->quoteIdentifier($joinColumn->getReferencedColumnName());

            $primaryKeyColumns[] = $quotedColumnName;
            $localColumns[]      = $quotedColumnName;
            $foreignColumns[]    = $quotedReferencedColumnName;

            if (! $theJoinTable->hasColumn($quotedColumnName)) {
                // Only add the column to the table if it does not exist already.
                // It might exist already if the foreign key is mapped into a regular
                // property as well.
                $property      = $definingClass->getProperty($referencedFieldName);
                $columnOptions = [
                    'notnull' => ! $joinColumn->isNullable(),
                ] + $this->gatherColumnOptions($property->getOptions());

                if (! empty($joinColumn->getColumnDefinition())) {
                    $columnOptions['columnDefinition'] = $joinColumn->getColumnDefinition();
                } elseif ($property->getColumnDefinition()) {
                    $columnOptions['columnDefinition'] = $property->getColumnDefinition();
                }

                $columnType = $property->getTypeName();

                switch ($columnType) {
                    case 'string':
                        $columnOptions['length'] = is_int($property->getLength()) ? $property->getLength() : 255;
                        break;

                    case 'decimal':
                        $columnOptions['scale']     = $property->getScale();
                        $columnOptions['precision'] = $property->getPrecision();
                        break;
                }

                $theJoinTable->addColumn($quotedColumnName, $columnType, $columnOptions);
            }

            if ($joinColumn->isUnique()) {
                $uniqueConstraints[] = ['columns' => [$quotedColumnName]];
            }

            if (! empty($joinColumn->getOnDelete())) {
                $fkOptions['onDelete'] = $joinColumn->getOnDelete();
            }
        }

        // Prefer unique constraints over implicit simple indexes created for foreign keys.
        // Also avoids index duplication.
        foreach ($uniqueConstraints as $indexName => $unique) {
            $theJoinTable->addUniqueIndex($unique['columns'], is_numeric($indexName) ? null : $indexName);
        }

        $compositeName = $theJoinTable->getName() . '.' . implode('', $localColumns);

        if (isset($addedFks[$compositeName])
            && ($foreignTableName !== $addedFks[$compositeName]['foreignTableName']
            || 0 < count(array_diff($foreignColumns, $addedFks[$compositeName]['foreignColumns'])))
        ) {
            foreach ($theJoinTable->getForeignKeys() as $fkName => $key) {
                if (count(array_diff($key->getLocalColumns(), $localColumns)) === 0
                    && (($key->getForeignTableName() !== $foreignTableName)
                    || 0 < count(array_diff($key->getForeignColumns(), $foreignColumns)))
                ) {
                    $theJoinTable->removeForeignKey($fkName);
                    break;
                }
            }

            $blacklistedFks[$compositeName] = true;
        } elseif (! isset($blacklistedFks[$compositeName])) {
            $addedFks[$compositeName] = [
                'foreignTableName' => $foreignTableName,
                'foreignColumns'   => $foreignColumns,
            ];

            $theJoinTable->addForeignKeyConstraint(
                $foreignTableName,
                $localColumns,
                $foreignColumns,
                $fkOptions
            );
        }
    }

    /**
     * @param mixed[] $mapping
     *
     * @return mixed[]
     */
    private function gatherColumnOptions(array $mapping) : array
    {
        if ($mapping === []) {
            return [];
        }

        $options                        = array_intersect_key($mapping, array_flip(self::KNOWN_COLUMN_OPTIONS));
        $options['customSchemaOptions'] = array_diff_key($mapping, $options);

        return $options;
    }

    /**
     * Drops the database schema for the given classes.
     *
     * In any way when an exception is thrown it is suppressed since drop was
     * issued for all classes of the schema and some probably just don't exist.
     *
     * @param ClassMetadata[] $classes
     */
    public function dropSchema(array $classes)
    {
        $dropSchemaSql = $this->getDropSchemaSQL($classes);
        $conn          = $this->em->getConnection();

        foreach ($dropSchemaSql as $sql) {
            try {
                $conn->executeQuery($sql);
            } catch (Throwable $e) {
                // ignored
            }
        }
    }

    /**
     * Drops all elements in the database of the current connection.
     */
    public function dropDatabase()
    {
        $dropSchemaSql = $this->getDropDatabaseSQL();
        $conn          = $this->em->getConnection();

        foreach ($dropSchemaSql as $sql) {
            $conn->executeQuery($sql);
        }
    }

    /**
     * Gets the SQL needed to drop the database schema for the connections database.
     *
     * @return string[]
     */
    public function getDropDatabaseSQL()
    {
        $sm     = $this->em->getConnection()->getSchemaManager();
        $schema = $sm->createSchema();

        $visitor = new DropSchemaSqlCollector($this->platform);
        $schema->visit($visitor);

        return $visitor->getQueries();
    }

    /**
     * Gets SQL to drop the tables defined by the passed classes.
     *
     * @param ClassMetadata[] $classes
     *
     * @return string[]
     */
    public function getDropSchemaSQL(array $classes)
    {
        $visitor = new DropSchemaSqlCollector($this->platform);
        $schema  = $this->getSchemaFromMetadata($classes);

        $sm         = $this->em->getConnection()->getSchemaManager();
        $fullSchema = $sm->createSchema();

        foreach ($fullSchema->getTables() as $table) {
            if (! $schema->hasTable($table->getName())) {
                foreach ($table->getForeignKeys() as $foreignKey) {
                    /** @var $foreignKey ForeignKeyConstraint */
                    if ($schema->hasTable($foreignKey->getForeignTableName())) {
                        $visitor->acceptForeignKey($table, $foreignKey);
                    }
                }
            } else {
                $visitor->acceptTable($table);
                foreach ($table->getForeignKeys() as $foreignKey) {
                    $visitor->acceptForeignKey($table, $foreignKey);
                }
            }
        }

        if ($this->platform->supportsSequences()) {
            foreach ($schema->getSequences() as $sequence) {
                $visitor->acceptSequence($sequence);
            }

            foreach ($schema->getTables() as $table) {
                /** @var $sequence Table */
                if ($table->hasPrimaryKey()) {
                    $columns = $table->getPrimaryKey()->getColumns();
                    if (count($columns) === 1) {
                        $checkSequence = $table->getName() . '_' . $columns[0] . '_seq';
                        if ($fullSchema->hasSequence($checkSequence)) {
                            $visitor->acceptSequence($fullSchema->getSequence($checkSequence));
                        }
                    }
                }
            }
        }

        return $visitor->getQueries();
    }

    /**
     * Updates the database schema of the given classes by comparing the ClassMetadata
     * instances to the current database schema that is inspected.
     *
     * @param ClassMetadata[] $classes
     * @param bool            $saveMode If TRUE, only performs a partial update
     *                                  without dropping assets which are scheduled for deletion.
     */
    public function updateSchema(array $classes, $saveMode = false)
    {
        $updateSchemaSql = $this->getUpdateSchemaSql($classes, $saveMode);
        $conn            = $this->em->getConnection();

        foreach ($updateSchemaSql as $sql) {
            $conn->executeQuery($sql);
        }
    }

    /**
     * Gets the sequence of SQL statements that need to be performed in order
     * to bring the given class mappings in-synch with the relational schema.
     *
     * @param ClassMetadata[] $classes  The classes to consider.
     * @param bool            $saveMode If TRUE, only generates SQL for a partial update
     *                                  that does not include SQL for dropping assets which are scheduled for deletion.
     *
     * @return string[] The sequence of SQL statements.
     */
    public function getUpdateSchemaSql(array $classes, $saveMode = false)
    {
        $sm = $this->em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $toSchema   = $this->getSchemaFromMetadata($classes);

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        if ($saveMode) {
            return $schemaDiff->toSaveSql($this->platform);
        }

        return $schemaDiff->toSql($this->platform);
    }
}
