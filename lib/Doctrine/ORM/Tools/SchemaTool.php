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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\ORMException,
    Doctrine\DBAL\Types\Type,
    Doctrine\DBAL\Schema\Schema,
    Doctrine\DBAL\Schema\Visitor\RemoveNamespacedAssets,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Internal\CommitOrderCalculator,
    Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs,
    Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

/**
 * The SchemaTool is a tool to create/drop/update database schemas based on
 * <tt>ClassMetadata</tt> class descriptors.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class SchemaTool
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $_platform;

    /**
     * Initializes a new SchemaTool instance that uses the connection of the
     * provided EntityManager.
     *
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->_platform = $em->getConnection()->getDatabasePlatform();
    }

    /**
     * Creates the database schema for the given array of ClassMetadata instances.
     *
     * @throws ToolsException
     * @param array $classes
     * @return void
     */
    public function createSchema(array $classes)
    {
        $createSchemaSql = $this->getCreateSchemaSql($classes);
        $conn = $this->_em->getConnection();

        foreach ($createSchemaSql as $sql) {
            try {
                $conn->executeQuery($sql);
            } catch(\Exception $e) {
                throw ToolsException::schemaToolFailure($sql, $e);
            }
        }
    }

    /**
     * Gets the list of DDL statements that are required to create the database schema for
     * the given list of ClassMetadata instances.
     *
     * @param array $classes
     * @return array $sql The SQL statements needed to create the schema for the classes.
     */
    public function getCreateSchemaSql(array $classes)
    {
        $schema = $this->getSchemaFromMetadata($classes);
        return $schema->toSql($this->_platform);
    }

    /**
     * Some instances of ClassMetadata don't need to be processed in the SchemaTool context. This method detects them.
     *
     * @param ClassMetadata $class
     * @param array $processedClasses
     * @return bool
     */
    private function processingNotRequired($class, array $processedClasses)
    {
        return (
            isset($processedClasses[$class->name]) ||
            $class->isMappedSuperclass ||
            ($class->isInheritanceTypeSingleTable() && $class->name != $class->rootEntityName)
        );
    }

    /**
     * From a given set of metadata classes this method creates a Schema instance.
     *
     * @param array $classes
     * @return Schema
     */
    public function getSchemaFromMetadata(array $classes)
    {
        $processedClasses = array(); // Reminder for processed classes, used for hierarchies

        $sm = $this->_em->getConnection()->getSchemaManager();
        $metadataSchemaConfig = $sm->createSchemaConfig();
        $metadataSchemaConfig->setExplicitForeignKeyIndexes(false);
        $schema = new Schema(array(), array(), $metadataSchemaConfig);

        $evm = $this->_em->getEventManager();

        foreach ($classes as $class) {
            if ($this->processingNotRequired($class, $processedClasses)) {
                continue;
            }

            $table = $schema->createTable($class->getQuotedTableName($this->_platform));

            $columns = array(); // table columns

            if ($class->isInheritanceTypeSingleTable()) {
                $columns = $this->_gatherColumns($class, $table);
                $this->_gatherRelationsSql($class, $table, $schema);

                // Add the discriminator column
                $this->addDiscriminatorColumnDefinition($class, $table);

                // Aggregate all the information from all classes in the hierarchy
                foreach ($class->parentClasses as $parentClassName) {
                    // Parent class information is already contained in this class
                    $processedClasses[$parentClassName] = true;
                }

                foreach ($class->subClasses as $subClassName) {
                    $subClass = $this->_em->getClassMetadata($subClassName);
                    $this->_gatherColumns($subClass, $table);
                    $this->_gatherRelationsSql($subClass, $table, $schema);
                    $processedClasses[$subClassName] = true;
                }
            } else if ($class->isInheritanceTypeJoined()) {
                // Add all non-inherited fields as columns
                $pkColumns = array();
                foreach ($class->fieldMappings as $fieldName => $mapping) {
                    if ( ! isset($mapping['inherited'])) {
                        $columnName = $class->getQuotedColumnName($mapping['fieldName'], $this->_platform);
                        $this->_gatherColumn($class, $mapping, $table);

                        if ($class->isIdentifier($fieldName)) {
                            $pkColumns[] = $columnName;
                        }
                    }
                }

                $this->_gatherRelationsSql($class, $table, $schema);

                // Add the discriminator column only to the root table
                if ($class->name == $class->rootEntityName) {
                    $this->addDiscriminatorColumnDefinition($class, $table);
                } else {
                    // Add an ID FK column to child tables
                    /* @var \Doctrine\ORM\Mapping\ClassMetadata $class */
                    $idMapping = $class->fieldMappings[$class->identifier[0]];
                    $this->_gatherColumn($class, $idMapping, $table);
                    $columnName = $class->getQuotedColumnName($class->identifier[0], $this->_platform);
                    // TODO: This seems rather hackish, can we optimize it?
                    $table->getColumn($columnName)->setAutoincrement(false);

                    $pkColumns[] = $columnName;

                    // Add a FK constraint on the ID column
                    $table->addUnnamedForeignKeyConstraint(
                        $this->_em->getClassMetadata($class->rootEntityName)->getQuotedTableName($this->_platform),
                        array($columnName), array($columnName), array('onDelete' => 'CASCADE')
                    );
                }

                $table->setPrimaryKey($pkColumns);

            } else if ($class->isInheritanceTypeTablePerClass()) {
                throw ORMException::notSupported();
            } else {
                $this->_gatherColumns($class, $table);
                $this->_gatherRelationsSql($class, $table, $schema);
            }

            $pkColumns = array();
            foreach ($class->identifier AS $identifierField) {
                if (isset($class->fieldMappings[$identifierField])) {
                    $pkColumns[] = $class->getQuotedColumnName($identifierField, $this->_platform);
                } else if (isset($class->associationMappings[$identifierField])) {
                    /* @var $assoc \Doctrine\ORM\Mapping\OneToOne */
                    $assoc = $class->associationMappings[$identifierField];
                    foreach ($assoc['joinColumns'] AS $joinColumn) {
                        $pkColumns[] = $joinColumn['name'];
                    }
                }
            }
            if (!$table->hasIndex('primary')) {
                $table->setPrimaryKey($pkColumns);
            }

            if (isset($class->table['indexes'])) {
                foreach ($class->table['indexes'] AS $indexName => $indexData) {
                    $table->addIndex($indexData['columns'], is_numeric($indexName) ? null : $indexName);
                }
            }

            if (isset($class->table['uniqueConstraints'])) {
                foreach ($class->table['uniqueConstraints'] AS $indexName => $indexData) {
                    $table->addUniqueIndex($indexData['columns'], is_numeric($indexName) ? null : $indexName);
                }
            }

            $processedClasses[$class->name] = true;

            if ($class->isIdGeneratorSequence() && $class->name == $class->rootEntityName) {
                $seqDef = $class->sequenceGeneratorDefinition;

                if (!$schema->hasSequence($seqDef['sequenceName'])) {
                    $schema->createSequence(
                        $seqDef['sequenceName'],
                        $seqDef['allocationSize'],
                        $seqDef['initialValue']
                    );
                }
            }

            if ($evm->hasListeners(ToolEvents::postGenerateSchemaTable)) {
                $evm->dispatchEvent(ToolEvents::postGenerateSchemaTable, new GenerateSchemaTableEventArgs($class, $schema, $table));
            }
        }

        if ( ! $this->_platform->supportsSchemas() && ! $this->_platform->canEmulateSchemas() ) {
            $schema->visit(new RemoveNamespacedAssets());
        }

        if ($evm->hasListeners(ToolEvents::postGenerateSchema)) {
            $evm->dispatchEvent(ToolEvents::postGenerateSchema, new GenerateSchemaEventArgs($this->_em, $schema));
        }

        return $schema;
    }

    /**
     * Gets a portable column definition as required by the DBAL for the discriminator
     * column of a class.
     *
     * @param ClassMetadata $class
     * @return array The portable column definition of the discriminator column as required by
     *              the DBAL.
     */
    private function addDiscriminatorColumnDefinition($class, $table)
    {
        $discrColumn = $class->discriminatorColumn;

        if (!isset($discrColumn['type']) || (strtolower($discrColumn['type']) == 'string' && $discrColumn['length'] === null)) {
            $discrColumn['type'] = 'string';
            $discrColumn['length'] = 255;
        }

        $table->addColumn(
            $discrColumn['name'],
            $discrColumn['type'],
            array('length' => $discrColumn['length'], 'notnull' => true)
        );
    }

    /**
     * Gathers the column definitions as required by the DBAL of all field mappings
     * found in the given class.
     *
     * @param ClassMetadata $class
     * @param Table $table
     * @return array The list of portable column definitions as required by the DBAL.
     */
    private function _gatherColumns($class, $table)
    {
        $columns = array();
        $pkColumns = array();

        foreach ($class->fieldMappings as $fieldName => $mapping) {
            if ($class->isInheritanceTypeSingleTable() && isset($mapping['inherited'])) {
                continue;
            }

            $column = $this->_gatherColumn($class, $mapping, $table);

            if ($class->isIdentifier($mapping['fieldName'])) {
                $pkColumns[] = $class->getQuotedColumnName($mapping['fieldName'], $this->_platform);
            }
        }

        // For now, this is a hack required for single table inheritence, since this method is called
        // twice by single table inheritence relations
        if(!$table->hasIndex('primary')) {
            //$table->setPrimaryKey($pkColumns);
        }

        return $columns;
    }

    /**
     * Creates a column definition as required by the DBAL from an ORM field mapping definition.
     *
     * @param ClassMetadata $class The class that owns the field mapping.
     * @param array $mapping The field mapping.
     * @param Table $table
     * @return array The portable column definition as required by the DBAL.
     */
    private function _gatherColumn($class, array $mapping, $table)
    {
        $columnName = $class->getQuotedColumnName($mapping['fieldName'], $this->_platform);
        $columnType = $mapping['type'];

        $options = array();
        $options['length'] = isset($mapping['length']) ? $mapping['length'] : null;
        $options['notnull'] = isset($mapping['nullable']) ? ! $mapping['nullable'] : true;
        if ($class->isInheritanceTypeSingleTable() && count($class->parentClasses) > 0) {
            $options['notnull'] = false;
        }

        $options['platformOptions'] = array();
        $options['platformOptions']['version'] = $class->isVersioned && $class->versionField == $mapping['fieldName'] ? true : false;

        if(strtolower($columnType) == 'string' && $options['length'] === null) {
            $options['length'] = 255;
        }

        if (isset($mapping['precision'])) {
            $options['precision'] = $mapping['precision'];
        }

        if (isset($mapping['scale'])) {
            $options['scale'] = $mapping['scale'];
        }

        if (isset($mapping['default'])) {
            $options['default'] = $mapping['default'];
        }

        if (isset($mapping['columnDefinition'])) {
            $options['columnDefinition'] = $mapping['columnDefinition'];
        }

        if (isset($mapping['options'])) {
            $options['customSchemaOptions'] = $mapping['options'];
        }

        if ($class->isIdGeneratorIdentity() && $class->getIdentifierFieldNames() == array($mapping['fieldName'])) {
            $options['autoincrement'] = true;
        }
        if ($class->isInheritanceTypeJoined() && $class->name != $class->rootEntityName) {
            $options['autoincrement'] = false;
        }

        if ($table->hasColumn($columnName)) {
            // required in some inheritance scenarios
            $table->changeColumn($columnName, $options);
        } else {
            $table->addColumn($columnName, $columnType, $options);
        }

        $isUnique = isset($mapping['unique']) ? $mapping['unique'] : false;
        if ($isUnique) {
            $table->addUniqueIndex(array($columnName));
        }
    }

    /**
     * Gathers the SQL for properly setting up the relations of the given class.
     * This includes the SQL for foreign key constraints and join tables.
     *
     * @param ClassMetadata $class
     * @param \Doctrine\DBAL\Schema\Table $table
     * @param \Doctrine\DBAL\Schema\Schema $schema
     * @return void
     */
    private function _gatherRelationsSql($class, $table, $schema)
    {
        foreach ($class->associationMappings as $fieldName => $mapping) {
            if (isset($mapping['inherited'])) {
                continue;
            }

            $foreignClass = $this->_em->getClassMetadata($mapping['targetEntity']);

            if ($mapping['type'] & ClassMetadata::TO_ONE && $mapping['isOwningSide']) {
                $primaryKeyColumns = $uniqueConstraints = array(); // PK is unnecessary for this relation-type

                $this->_gatherRelationJoinColumns($mapping['joinColumns'], $table, $foreignClass, $mapping, $primaryKeyColumns, $uniqueConstraints);

                foreach($uniqueConstraints AS $indexName => $unique) {
                    $table->addUniqueIndex($unique['columns'], is_numeric($indexName) ? null : $indexName);
                }
            } else if ($mapping['type'] == ClassMetadata::ONE_TO_MANY && $mapping['isOwningSide']) {
                //... create join table, one-many through join table supported later
                throw ORMException::notSupported();
            } else if ($mapping['type'] == ClassMetadata::MANY_TO_MANY && $mapping['isOwningSide']) {
                // create join table
                $joinTable = $mapping['joinTable'];

                $theJoinTable = $schema->createTable($foreignClass->getQuotedJoinTableName($mapping, $this->_platform));

                $primaryKeyColumns = $uniqueConstraints = array();

                // Build first FK constraint (relation table => source table)
                $this->_gatherRelationJoinColumns($joinTable['joinColumns'], $theJoinTable, $class, $mapping, $primaryKeyColumns, $uniqueConstraints);

                // Build second FK constraint (relation table => target table)
                $this->_gatherRelationJoinColumns($joinTable['inverseJoinColumns'], $theJoinTable, $foreignClass, $mapping, $primaryKeyColumns, $uniqueConstraints);

                $theJoinTable->setPrimaryKey($primaryKeyColumns);

                foreach($uniqueConstraints AS $indexName => $unique) {
                    $theJoinTable->addUniqueIndex($unique['columns'], is_numeric($indexName) ? null : $indexName);
                }
            }
        }
    }

    /**
     * Get the class metadata that is responsible for the definition of the referenced column name.
     *
     * Previously this was a simple task, but with DDC-117 this problem is actually recursive. If its
     * not a simple field, go through all identifier field names that are associations recursivly and
     * find that referenced column name.
     *
     * TODO: Is there any way to make this code more pleasing?
     *
     * @param ClassMetadata $class
     * @param string $referencedColumnName
     * @return array(ClassMetadata, referencedFieldName)
     */
    private function getDefiningClass($class, $referencedColumnName)
    {
        $referencedFieldName = $class->getFieldName($referencedColumnName);

        if ($class->hasField($referencedFieldName)) {
            return array($class, $referencedFieldName);
        } else if (in_array($referencedColumnName, $class->getIdentifierColumnNames())) {
            // it seems to be an entity as foreign key
            foreach ($class->getIdentifierFieldNames() AS $fieldName) {
                if ($class->hasAssociation($fieldName) && $class->getSingleAssociationJoinColumnName($fieldName) == $referencedColumnName) {
                    return $this->getDefiningClass(
                        $this->_em->getClassMetadata($class->associationMappings[$fieldName]['targetEntity']),
                        $class->getSingleAssociationReferencedJoinColumnName($fieldName)
                    );
                }
            }
        }

        return null;
    }

    /**
     * Gather columns and fk constraints that are required for one part of relationship.
     *
     * @param array $joinColumns
     * @param \Doctrine\DBAL\Schema\Table $theJoinTable
     * @param ClassMetadata $class
     * @param array $mapping
     * @param array $primaryKeyColumns
     * @param array $uniqueConstraints
     */
    private function _gatherRelationJoinColumns($joinColumns, $theJoinTable, $class, $mapping, &$primaryKeyColumns, &$uniqueConstraints)
    {
        $localColumns = array();
        $foreignColumns = array();
        $fkOptions = array();
        $foreignTableName = $class->getQuotedTableName($this->_platform);

        foreach ($joinColumns as $joinColumn) {
            $columnName = $joinColumn['name'];
            list($definingClass, $referencedFieldName) = $this->getDefiningClass($class, $joinColumn['referencedColumnName']);

            if (!$definingClass) {
                throw new \Doctrine\ORM\ORMException(
                    "Column name `".$joinColumn['referencedColumnName']."` referenced for relation from ".
                    $mapping['sourceEntity'] . " towards ". $mapping['targetEntity'] . " does not exist."
                );
            }

            $primaryKeyColumns[] = $columnName;
            $localColumns[] = $columnName;
            $foreignColumns[] = $joinColumn['referencedColumnName'];

            if ( ! $theJoinTable->hasColumn($joinColumn['name'])) {
                // Only add the column to the table if it does not exist already.
                // It might exist already if the foreign key is mapped into a regular
                // property as well.

                $fieldMapping = $definingClass->getFieldMapping($referencedFieldName);

                $columnDef = null;
                if (isset($joinColumn['columnDefinition'])) {
                    $columnDef = $joinColumn['columnDefinition'];
                } else if (isset($fieldMapping['columnDefinition'])) {
                    $columnDef = $fieldMapping['columnDefinition'];
                }
                $columnOptions = array('notnull' => false, 'columnDefinition' => $columnDef);
                if (isset($joinColumn['nullable'])) {
                    $columnOptions['notnull'] = !$joinColumn['nullable'];
                }
                if ($fieldMapping['type'] == "string" && isset($fieldMapping['length'])) {
                    $columnOptions['length'] = $fieldMapping['length'];
                } else if ($fieldMapping['type'] == "decimal") {
                    $columnOptions['scale'] = $fieldMapping['scale'];
                    $columnOptions['precision'] = $fieldMapping['precision'];
                }

                $theJoinTable->addColumn($columnName, $fieldMapping['type'], $columnOptions);
            }

            if (isset($joinColumn['unique']) && $joinColumn['unique'] == true) {
                $uniqueConstraints[] = array('columns' => array($columnName));
            }

            if (isset($joinColumn['onDelete'])) {
                $fkOptions['onDelete'] = $joinColumn['onDelete'];
            }
        }

        $theJoinTable->addUnnamedForeignKeyConstraint(
            $foreignTableName, $localColumns, $foreignColumns, $fkOptions
        );
    }

    /**
     * Drops the database schema for the given classes.
     *
     * In any way when an exception is thrown it is supressed since drop was
     * issued for all classes of the schema and some probably just don't exist.
     *
     * @param array $classes
     * @return void
     */
    public function dropSchema(array $classes)
    {
        $dropSchemaSql = $this->getDropSchemaSQL($classes);
        $conn = $this->_em->getConnection();

        foreach ($dropSchemaSql as $sql) {
            try {
                $conn->executeQuery($sql);
            } catch(\Exception $e) {

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
        $conn = $this->_em->getConnection();

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
        $sm = $this->_em->getConnection()->getSchemaManager();
        $schema = $sm->createSchema();

        $visitor = new \Doctrine\DBAL\Schema\Visitor\DropSchemaSqlCollector($this->_platform);
        /* @var $schema \Doctrine\DBAL\Schema\Schema */
        $schema->visit($visitor);
        return $visitor->getQueries();
    }

    /**
     * Get SQL to drop the tables defined by the passed classes.
     *
     * @param array $classes
     * @return array
     */
    public function getDropSchemaSQL(array $classes)
    {
        $visitor = new \Doctrine\DBAL\Schema\Visitor\DropSchemaSqlCollector($this->_platform);
        $schema = $this->getSchemaFromMetadata($classes);

        $sm = $this->_em->getConnection()->getSchemaManager();
        $fullSchema = $sm->createSchema();
        foreach ($fullSchema->getTables() AS $table) {
            if (!$schema->hasTable($table->getName())) {
                foreach ($table->getForeignKeys() AS $foreignKey) {
                    /* @var $foreignKey \Doctrine\DBAL\Schema\ForeignKeyConstraint */
                    if ($schema->hasTable($foreignKey->getForeignTableName())) {
                        $visitor->acceptForeignKey($table, $foreignKey);
                    }
                }
            } else {
                $visitor->acceptTable($table);
                foreach ($table->getForeignKeys() AS $foreignKey) {
                    $visitor->acceptForeignKey($table, $foreignKey);
                }
            }
        }

        if ($this->_platform->supportsSequences()) {
            foreach ($schema->getSequences() AS $sequence) {
                $visitor->acceptSequence($sequence);
            }
            foreach ($schema->getTables() AS $table) {
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
     * instances to the current database schema that is inspected. If $saveMode is set
     * to true the command is executed in the Database, else SQL is returned.
     *
     * @param array $classes
     * @param boolean $saveMode
     * @return void
     */
    public function updateSchema(array $classes, $saveMode=false)
    {
        $updateSchemaSql = $this->getUpdateSchemaSql($classes, $saveMode);
        $conn = $this->_em->getConnection();

        foreach ($updateSchemaSql as $sql) {
            $conn->executeQuery($sql);
        }
    }

    /**
     * Gets the sequence of SQL statements that need to be performed in order
     * to bring the given class mappings in-synch with the relational schema.
     * If $saveMode is set to true the command is executed in the Database,
     * else SQL is returned.
     *
     * @param array $classes The classes to consider.
     * @param boolean $saveMode True for writing to DB, false for SQL string
     * @return array The sequence of SQL statements.
     */
    public function getUpdateSchemaSql(array $classes, $saveMode=false)
    {
        $sm = $this->_em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $toSchema = $this->getSchemaFromMetadata($classes);

        $comparator = new \Doctrine\DBAL\Schema\Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        if ($saveMode) {
            return $schemaDiff->toSaveSql($this->_platform);
        } else {
            return $schemaDiff->toSql($this->_platform);
        }
    }
}
