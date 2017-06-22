<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools;

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
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\GeneratorType;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;

/**
 * The SchemaTool is a tool to create/drop/update database schemas based on
 * <tt>ClassMetadata</tt> class descriptors.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Stefano Rodriguez <stefano.rodriguez@fubles.com>
 */
class SchemaTool
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    /**
     * Initializes a new SchemaTool instance that uses the connection of the
     * provided EntityManager.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em       = $em;
        $this->platform = $em->getConnection()->getDatabasePlatform();
    }

    /**
     * Creates the database schema for the given array of ClassMetadata instances.
     *
     * @param array $classes
     *
     * @return void
     *
     * @throws ToolsException
     */
    public function createSchema(array $classes)
    {
        $createSchemaSql = $this->getCreateSchemaSql($classes);
        $conn = $this->em->getConnection();

        foreach ($createSchemaSql as $sql) {
            try {
                $conn->executeQuery($sql);
            } catch (\Exception $e) {
                throw ToolsException::schemaToolFailure($sql, $e);
            }
        }
    }

    /**
     * Gets the list of DDL statements that are required to create the database schema for
     * the given list of ClassMetadata instances.
     *
     * @param array $classes
     *
     * @return array The SQL statements needed to create the schema for the classes.
     */
    public function getCreateSchemaSql(array $classes)
    {
        $schema = $this->getSchemaFromMetadata($classes);

        return $schema->toSql($this->platform);
    }

    /**
     * Detects instances of ClassMetadata that don't need to be processed in the SchemaTool context.
     *
     * @param ClassMetadata $class
     * @param array         $processedClasses
     *
     * @return bool
     */
    private function processingNotRequired($class, array $processedClasses)
    {
        return (
            isset($processedClasses[$class->name]) ||
            $class->isMappedSuperclass ||
            $class->isEmbeddedClass ||
            ($class->inheritanceType === InheritanceType::SINGLE_TABLE && $class->name !== $class->rootEntityName)
        );
    }

    /**
     * Creates a Schema instance from a given set of metadata classes.
     *
     * @param array $classes
     *
     * @return Schema
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getSchemaFromMetadata(array $classes)
    {
        // Reminder for processed classes, used for hierarchies
        $processedClasses       = [];
        $eventManager           = $this->em->getEventManager();
        $schemaManager          = $this->em->getConnection()->getSchemaManager();
        $metadataSchemaConfig   = $schemaManager->createSchemaConfig();

        $metadataSchemaConfig->setExplicitForeignKeyIndexes(false);
        $schema = new Schema([], [], $metadataSchemaConfig);

        $addedFks = [];
        $blacklistedFks = [];

        foreach ($classes as $class) {
            /** @var \Doctrine\ORM\Mapping\ClassMetadata $class */
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

                    break;

                case InheritanceType::JOINED:
                    // Add all non-inherited fields as columns
                    $pkColumns = [];

                    foreach ($class->getProperties() as $fieldName => $property) {
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
                    if ($class->name === $class->rootEntityName) {
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

                                $pkColumns[] = $columnName;
                                $inheritedKeyColumns[] = $columnName;
                            }
                        }

                        if ( ! empty($inheritedKeyColumns)) {
                            // Add a FK constraint on the ID column
                            $rootClass = $this->em->getClassMetadata($class->rootEntityName);

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
                    throw ORMException::notSupported();

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

            if ( ! $table->hasIndex('primary')) {
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
                    $index     = new Index($indexName, $indexData['columns'], $indexData['unique'], $indexData['flags'], $indexData['options']);

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

            $processedClasses[$class->name] = true;

            if ($class->generatorType === GeneratorType::SEQUENCE && $class->name === $class->rootEntityName) {
                $definition = $class->generatorDefinition;
                $quotedName = $this->platform->quoteIdentifier($definition['sequenceName']);

                if ( ! $schema->hasSequence($quotedName)) {
                    $schema->createSequence($quotedName, $definition['allocationSize']);
                }
            }

            if ($eventManager->hasListeners(ToolEvents::postGenerateSchemaTable)) {
                $eventManager->dispatchEvent(
                    ToolEvents::postGenerateSchemaTable,
                    new GenerateSchemaTableEventArgs($class, $schema, $table)
                );
            }
        }

        if ( ! $this->platform->supportsSchemas() && ! $this->platform->canEmulateSchemas() ) {
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
     * @param Table         $table
     *
     * @return void
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
                $options['scale'] = $discrColumn->getScale();
                $options['precision'] = $discrColumn->getPrecision();
                break;
        }

        if (!empty($discrColumn->getColumnDefinition())) {
            $options['columnDefinition'] = $discrColumn->getColumnDefinition();
        }

        $table->addColumn($discrColumn->getColumnName(), $discrColumnType, $options);
    }

    /**
     * Gathers the column definitions as required by the DBAL of all field mappings
     * found in the given class.
     *
     * @param ClassMetadata $class
     * @param Table         $table
     *
     * @return void
     */
    private function gatherColumns($class, Table $table)
    {
        $pkColumns = [];

        foreach ($class->getProperties() as $fieldName => $property) {
            if (! ($property instanceof FieldMetadata)) {
                continue;
            }

            if ($class->inheritanceType === InheritanceType::SINGLE_TABLE && $class->isInheritedProperty($fieldName)) {
                continue;
            }

            $this->gatherColumn($class, $property, $table);

            if ($property->isPrimaryKey()) {
                $pkColumns[] = $this->platform->quoteIdentifier($property->getColumnName());
            }
        }
    }

    /**
     * Creates a column definition as required by the DBAL from an ORM field mapping definition.
     *
     * @param ClassMetadata $classMetadata The class that owns the field mapping.
     * @param FieldMetadata $fieldMetadata The field mapping.
     * @param Table         $table
     *
     * @return Column The portable column definition as required by the DBAL.
     */
    private function gatherColumn($classMetadata, FieldMetadata $fieldMetadata, Table $table)
    {
        $fieldName  = $fieldMetadata->getName();
        $columnName = $fieldMetadata->getColumnName();
        $columnType = $fieldMetadata->getTypeName();

        $options = [
            'length'          => $fieldMetadata->getLength(),
            'notnull'         => ! $fieldMetadata->isNullable(),
            'platformOptions' => [
                'version' => ($classMetadata->isVersioned() && $classMetadata->versionProperty->getName() === $fieldName),
            ],
        ];

        if ($classMetadata->inheritanceType === InheritanceType::SINGLE_TABLE && count($classMetadata->parentClasses) > 0) {
            $options['notnull'] = false;
        }

        if (strtolower($columnType) === 'string' && null === $options['length']) {
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

        if ($fieldOptions) {
            $knownOptions = ['comment', 'unsigned', 'fixed', 'default'];

            foreach ($knownOptions as $knownOption) {
                if (array_key_exists($knownOption, $fieldOptions)) {
                    $options[$knownOption] = $fieldOptions[$knownOption];

                    unset($fieldOptions[$knownOption]);
                }
            }

            $options['customSchemaOptions'] = $fieldOptions;
        }

        if ($classMetadata->generatorType === GeneratorType::IDENTITY && $classMetadata->getIdentifierFieldNames() == [$fieldName]) {
            $options['autoincrement'] = true;
        }

        if ($classMetadata->inheritanceType === InheritanceType::JOINED && $classMetadata->name !== $classMetadata->rootEntityName) {
            $options['autoincrement'] = false;
        }

        $quotedColumnName = $this->platform->quoteIdentifier($fieldMetadata->getColumnName());

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
     * @param array         $addedFks
     * @param array         $blacklistedFks
     *
     * @return void
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function gatherRelationsSql($class, $table, $schema, &$addedFks, &$blacklistedFks)
    {
        foreach ($class->getProperties() as $fieldName => $property) {
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
                case ($property instanceof ToOneAssociationMetadata):
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

                case ($property instanceof OneToManyAssociationMetadata):
                    //... create join table, one-many through join table supported later
                    throw ORMException::notSupported();

                case ($property instanceof ManyToManyAssociationMetadata):
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
     * @return array (ClassMetadata, referencedFieldName)
     */
    private function getDefiningClass($class, $referencedColumnName)
    {
        if (isset($class->fieldNames[$referencedColumnName])) {
            $referencedFieldName = $class->fieldNames[$referencedColumnName];

            if ($class->hasField($referencedFieldName)) {
                return [$class, $referencedFieldName];
            }
        }

        $idColumns        = $class->getIdentifierColumns($this->em);
        $idColumnNameList = array_keys($idColumns);

        if (! in_array($referencedColumnName, $idColumnNameList)) {
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
                throw MappingException::noSingleAssociationJoinColumnFound($class->name, $fieldName);
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
     * @param array         $joinColumns
     * @param Table         $theJoinTable
     * @param ClassMetadata $class
     * @param array         $mapping
     * @param array         $primaryKeyColumns
     * @param array         $addedFks
     * @param array         $blacklistedFks
     *
     * @return void
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function gatherRelationJoinColumns(
        $joinColumns,
        $theJoinTable,
        $class,
        $mapping,
        &$primaryKeyColumns,
        &$addedFks,
        &$blacklistedFks
    )
    {
        $localColumns       = [];
        $foreignColumns     = [];
        $fkOptions          = [];
        $foreignTableName   = $class->table->getQuotedQualifiedName($this->platform);
        $uniqueConstraints  = [];

        foreach ($joinColumns as $joinColumn) {
            list($definingClass, $referencedFieldName) = $this->getDefiningClass(
                $class,
                $joinColumn->getReferencedColumnName()
            );

            if ( ! $definingClass) {
                throw new \Doctrine\ORM\ORMException(sprintf(
                    'Column name "%s" referenced for relation from %s towards %s does not exist.',
                    $joinColumn->getReferencedColumnName(),
                    $mapping->getSourceEntity(),
                    $mapping->getTargetEntity()
                ));
            }

            $quotedColumnName           = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $quotedReferencedColumnName = $this->platform->quoteIdentifier($joinColumn->getReferencedColumnName());

            $primaryKeyColumns[]    = $quotedColumnName;
            $localColumns[]         = $quotedColumnName;
            $foreignColumns[]       = $quotedReferencedColumnName;

            if ( ! $theJoinTable->hasColumn($quotedColumnName)) {
                // Only add the column to the table if it does not exist already.
                // It might exist already if the foreign key is mapped into a regular
                // property as well.
                $property  = $definingClass->getProperty($referencedFieldName);
                $columnDef = null;

                if (!empty($joinColumn->getColumnDefinition())) {
                    $columnDef = $joinColumn->getColumnDefinition();
                } elseif ($property->getColumnDefinition()) {
                    $columnDef = $property->getColumnDefinition();
                }

                $columnType    = $property->getTypeName();
                $columnOptions = [
                    'notnull'          => !$joinColumn->isNullable(),
                    'columnDefinition' => $columnDef,
                ];

                if ($property->getOptions()) {
                    $columnOptions['options'] = $property->getOptions();
                }

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

            if (!empty($joinColumn->getOnDelete())) {
                $fkOptions['onDelete'] = $joinColumn->getOnDelete();
            }
        }

        // Prefer unique constraints over implicit simple indexes created for foreign keys.
        // Also avoids index duplication.
        foreach ($uniqueConstraints as $indexName => $unique) {
            $theJoinTable->addUniqueIndex($unique['columns'], is_numeric($indexName) ? null : $indexName);
        }

        $compositeName = $theJoinTable->getName().'.'.implode('', $localColumns);

        if (isset($addedFks[$compositeName])
            && ($foreignTableName != $addedFks[$compositeName]['foreignTableName']
            || 0 < count(array_diff($foreignColumns, $addedFks[$compositeName]['foreignColumns'])))
        ) {
            foreach ($theJoinTable->getForeignKeys() as $fkName => $key) {
                if (0 === count(array_diff($key->getLocalColumns(), $localColumns))
                    && (($key->getForeignTableName() != $foreignTableName)
                    || 0 < count(array_diff($key->getForeignColumns(), $foreignColumns)))
                ) {
                    $theJoinTable->removeForeignKey($fkName);
                    break;
                }
            }

            $blacklistedFks[$compositeName] = true;
        } elseif (!isset($blacklistedFks[$compositeName])) {
            $addedFks[$compositeName] = [
                'foreignTableName' => $foreignTableName,
                'foreignColumns'   => $foreignColumns
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
     * Drops the database schema for the given classes.
     *
     * In any way when an exception is thrown it is suppressed since drop was
     * issued for all classes of the schema and some probably just don't exist.
     *
     * @param array $classes
     *
     * @return void
     */
    public function dropSchema(array $classes)
    {
        $dropSchemaSql = $this->getDropSchemaSQL($classes);
        $conn = $this->em->getConnection();

        foreach ($dropSchemaSql as $sql) {
            try {
                $conn->executeQuery($sql);
            } catch (\Exception $e) {

            }
        }
    }

    /**
     * Drops all elements in the database of the current connection.
     *
     * @return void
     */
    public function dropDatabase()
    {
        $dropSchemaSql = $this->getDropDatabaseSQL();
        $conn = $this->em->getConnection();

        foreach ($dropSchemaSql as $sql) {
            $conn->executeQuery($sql);
        }
    }

    /**
     * Gets the SQL needed to drop the database schema for the connections database.
     *
     * @return array
     */
    public function getDropDatabaseSQL()
    {
        $sm = $this->em->getConnection()->getSchemaManager();
        $schema = $sm->createSchema();

        $visitor = new DropSchemaSqlCollector($this->platform);
        $schema->visit($visitor);

        return $visitor->getQueries();
    }

    /**
     * Gets SQL to drop the tables defined by the passed classes.
     *
     * @param array $classes
     *
     * @return array
     */
    public function getDropSchemaSQL(array $classes)
    {
        $visitor = new DropSchemaSqlCollector($this->platform);
        $schema = $this->getSchemaFromMetadata($classes);

        $sm = $this->em->getConnection()->getSchemaManager();
        $fullSchema = $sm->createSchema();

        foreach ($fullSchema->getTables() as $table) {
            if ( ! $schema->hasTable($table->getName())) {
                foreach ($table->getForeignKeys() as $foreignKey) {
                    /* @var $foreignKey \Doctrine\DBAL\Schema\ForeignKeyConstraint */
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
                /* @var $sequence Table */
                if ($table->hasPrimaryKey()) {
                    $columns = $table->getPrimaryKey()->getColumns();
                    if (count($columns) == 1) {
                        $checkSequence = $table->getName() . "_" . $columns[0] . "_seq";
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
     * @param array   $classes
     * @param boolean $saveMode If TRUE, only performs a partial update
     *                          without dropping assets which are scheduled for deletion.
     *
     * @return void
     */
    public function updateSchema(array $classes, $saveMode = false)
    {
        $updateSchemaSql = $this->getUpdateSchemaSql($classes, $saveMode);
        $conn = $this->em->getConnection();

        foreach ($updateSchemaSql as $sql) {
            $conn->executeQuery($sql);
        }
    }

    /**
     * Gets the sequence of SQL statements that need to be performed in order
     * to bring the given class mappings in-synch with the relational schema.
     *
     * @param array   $classes  The classes to consider.
     * @param boolean $saveMode If TRUE, only generates SQL for a partial update
     *                          that does not include SQL for dropping assets which are scheduled for deletion.
     *
     * @return array The sequence of SQL statements.
     */
    public function getUpdateSchemaSql(array $classes, $saveMode = false)
    {
        $sm = $this->em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $toSchema = $this->getSchemaFromMetadata($classes);

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        if ($saveMode) {
            return $schemaDiff->toSaveSql($this->platform);
        }

        return $schemaDiff->toSql($this->platform);
    }
}
