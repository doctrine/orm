<?php
/*
 *  $Id$
 *
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

use Doctrine\DBAL\Types\Type,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Internal\CommitOrderCalculator;

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
     * @var string
     */
    const DROP_METADATA = "metadata";
    /**
     * @var string
     */
    const DROP_DATABASE = "database";

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
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->_platform = $em->getConnection()->getDatabasePlatform();
    }

    /**
     * Creates the database schema for the given array of ClassMetadata instances.
     *
     * @param array $classes
     */
    public function createSchema(array $classes)
    {
        $createSchemaSql = $this->getCreateSchemaSql($classes);
        $conn = $this->_em->getConnection();
        
        foreach ($createSchemaSql as $sql) {
            $conn->execute($sql);
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
        $sql = array(); // All SQL statements
        $processedClasses = array(); // Reminder for processed classes, used for hierarchies
        $sequences = array(); // Sequence SQL statements. Appended to $sql at the end.

        $schema = new \Doctrine\DBAL\Schema\Schema();

        foreach ($classes as $class) {
            if (isset($processedClasses[$class->name]) || $class->isMappedSuperclass) {
                continue;
            }

            $table = $schema->createTable($class->getQuotedTableName($this->_platform));

            if ($class->isIdGeneratorIdentity()) {
                $table->setIdGeneratorType(\Doctrine\DBAL\Schema\Table::ID_IDENTITY);
            } else if ($class->isIdGeneratorSequence()) {
                $table->setIdGeneratorType(\Doctrine\DBAL\Schema\Table::ID_SEQUENCE);
            }

            $columns = array(); // table columns
            
            if ($class->isInheritanceTypeSingleTable()) {
                $columns = $this->_gatherColumns($class, $table);
                $this->_gatherRelationsSql($class, $sql, $columns, $table, $schema);
                
                // Add the discriminator column
                $discrColumnDef = $this->_getDiscriminatorColumnDefinition($class, $table);

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
                    $discrColumnDef = $this->_getDiscriminatorColumnDefinition($class, $table);
                } else {
                    // Add an ID FK column to child tables
                    /* @var Doctrine\ORM\Mapping\ClassMetadata $class */
                    $idMapping = $class->fieldMappings[$class->identifier[0]];
                    $this->_gatherColumn($class, $idMapping, $table);
                    $columnName = $class->getQuotedColumnName($class->identifier[0], $this->_platform);
                    
                    $pkColumns[] = $columnName;
                    if ($table->isIdGeneratorIdentity()) {
                       $table->setIdGeneratorType(\Doctrine\DBAL\Schema\Table::ID_NONE);
                    }
                    
                    // Add a FK constraint on the ID column
                    $table->addForeignKeyConstraint(
                        $this->_em->getClassMetadata($class->rootEntityName)->getQuotedTableName($this->_platform),
                        array($columnName), array($columnName), null,
                        array('onDelete' => 'CASCADE')
                    );
                }

                $table->setPrimaryKey($pkColumns);

            } else if ($class->isInheritanceTypeTablePerClass()) {
                throw DoctrineException::notSupported();
            } else {
                $this->_gatherColumns($class, $table);
                $this->_gatherRelationsSql($class, $table, $schema);
            }
            
            if (isset($class->primaryTable['indexes'])) {
                foreach ($class->primaryTable['indexes'] AS $indexName => $indexData) {
                    $table->addIndex($indexData, $indexName);
                }
            }
            
            if (isset($class->primaryTable['uniqueConstraints'])) {
                foreach ($class->primaryTable['uniqueConstraints'] AS $indexName => $indexData) {
                    $table->addUniqueIndex($indexData, $indexName);
                }
            }

            $processedClasses[$class->name] = true;

            if ($class->isIdGeneratorSequence() && $class->name == $class->rootEntityName) {
                $seqDef = $class->getSequenceGeneratorDefinition();

                if (!$schema->hasSequence($seqDef['sequenceName'])) {
                    $schema->createSequence(
                        $seqDef['sequenceName'],
                        $seqDef['allocationSize'],
                        $seqDef['initialValue']
                    );
                }
            }
        }
        
        return $schema->toSql($this->_platform);
    }

    /**
     * Gets a portable column definition as required by the DBAL for the discriminator
     * column of a class.
     * 
     * @param ClassMetadata $class
     * @return array The portable column definition of the discriminator column as required by
     *              the DBAL.
     */
    private function _getDiscriminatorColumnDefinition($class, $table)
    {
        $discrColumn = $class->discriminatorColumn;

        $table->createColumn(
            $class->getQuotedDiscriminatorColumnName($this->_platform),
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
            $column = $this->_gatherColumn($class, $mapping, $table);
            
            if ($class->isIdentifier($mapping['fieldName'])) {
                $pkColumns[] = $class->getQuotedColumnName($mapping['fieldName'], $this->_platform);
            }
        }
        // For now, this is a hack required for single table inheritence, since this method is called
        // twice by single table inheritence relations
        if(!$table->hasIndex('primary')) {
            $table->setPrimaryKey($pkColumns);
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

        $options['platformOptions'] = array();
        $options['platformOptions']['unique'] = isset($mapping['unique']) ? $mapping['unique'] : false;
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

        if ($table->hasColumn($columnName)) {
            // required in some inheritence scenarios
            $table->changeColumn($columnName, $options);
        } else {
            $table->createColumn($columnName, $columnType, $options);
        }
    }

    /**
     * Gathers the SQL for properly setting up the relations of the given class.
     * This includes the SQL for foreign key constraints and join tables.
     * 
     * @param ClassMetadata $class
     * @param array $sql The sequence of SQL statements where any new statements should be appended.
     * @param array $columns The list of columns in the class's primary table where any additional
     *          columns required by relations should be appended.
     * @param array $constraints The constraints of the table where any additional constraints
     *          required by relations should be appended.
     * @return void
     */
    private function _gatherRelationsSql($class, $table, $schema)
    {
        foreach ($class->associationMappings as $fieldName => $mapping) {
            if (isset($class->inheritedAssociationFields[$fieldName])) {
                continue;
            }

            $foreignClass = $this->_em->getClassMetadata($mapping->targetEntityName);
            
            if ($mapping->isOneToOne() && $mapping->isOwningSide) {
                $options = array();
                $localColumns = array();
                $foreignColumns = array();
                
                foreach ($mapping->getJoinColumns() as $joinColumn) {
                    $columnName = $mapping->getQuotedJoinColumnName($joinColumn['name'], $this->_platform);
                    $referencedColumnName = $joinColumn['referencedColumnName'];
                    $referencedFieldName = $foreignClass->getFieldName($referencedColumnName);
                    if (!$foreignClass->hasField($referencedFieldName)) {
                        throw new \Doctrine\Common\DoctrineException(
                            "Column name `$referencedColumnName` referenced for relation from ".
                            "$mapping->sourceEntityName towards $mapping->targetEntityName does not exist."
                        );
                    }

                    $table->createColumn(
                        $columnName, $foreignClass->getTypeOfField($referencedFieldName), array('notnull' => false)
                    );

                    $localColumns[] = $columnName;
                    $foreignColumns[] = $joinColumn['referencedColumnName'];
                    
                    if (isset($joinColumn['onUpdate'])) {
                        $options['onUpdate'] = $joinColumn['onUpdate'];
                    }
                    
                    if (isset($joinColumn['onDelete'])) {
                        $options['onDelete'] = $joinColumn['onDelete'];
                    }
                }

                $table->addForeignKeyConstraint(
                    $foreignClass->getQuotedTableName($this->_platform),
                    $localColumns, $foreignColumns, null, $options
                );
            } else if ($mapping->isOneToMany() && $mapping->isOwningSide) {
                //... create join table, one-many through join table supported later
                throw DoctrineException::notSupported();
            } else if ($mapping->isManyToMany() && $mapping->isOwningSide) {
                // create join table
                $joinTable = $mapping->getJoinTable();

                $localColumns = array();
                $foreignColumns = array();
                $fkOptions = array();

                $theJoinTable = $schema->createTable($mapping->getQuotedJoinTableName($this->_platform));

                $primaryKeyColumns = array();
                $uniqueConstraints = array();
                foreach ($joinTable['joinColumns'] as $joinColumn) {
                    $columnName = $mapping->getQuotedJoinColumnName($joinColumn['name'], $this->_platform);

                    $theJoinTable->createColumn(
                        $columnName,
                        $class->getTypeOfColumn($joinColumn['referencedColumnName']),
                        array('notnull' => false)
                    );

                    $primaryKeyColumns[] = $columnName;

                    $localColumns[] = $columnName;
                    $foreignColumns[] = $joinColumn['referencedColumnName'];

                    if(isset($joinColumn['unique']) && $joinColumn['unique'] == true) {
                        $uniqueConstraints[] = array($joinColumn['name']);
                    }
                    
                    if (isset($joinColumn['onUpdate'])) {
                        $fkOptions['onUpdate'] = $joinColumn['onUpdate'];
                    }
                    
                    if (isset($joinColumn['onDelete'])) {
                        $fkOptions['onDelete'] = $joinColumn['onDelete'];
                    }
                }

                // Build first FK constraint (relation table => source table)
                $theJoinTable->addForeignKeyConstraint(
                    $class->getQuotedTableName($this->_platform), $localColumns, $foreignColumns, null, $fkOptions
                );

                $localColumns = array();
                $foreignColumns = array();
                $fkOptions = array();
                
                foreach ($joinTable['inverseJoinColumns'] as $inverseJoinColumn) {
                    $primaryKeyColumns[] = $inverseJoinColumn['name'];
                    $localColumns[] = $inverseJoinColumn['name'];
                    $foreignColumns[] = $inverseJoinColumn['referencedColumnName'];

                    $theJoinTable->createColumn(
                        $inverseJoinColumn['name'],
                        $this->_em->getClassMetadata($mapping->getTargetEntityName())
                            ->getTypeOfColumn($inverseJoinColumn['referencedColumnName']),
                        array('notnull' => false)
                    );

                    if(isset($inverseJoinColumn['unique']) && $inverseJoinColumn['unique'] == true) {
                        $uniqueConstraints[] = array($inverseJoinColumn['name']);
                    }
                    
                    if (isset($inverseJoinColumn['onUpdate'])) {
                        $fkOptions['onUpdate'] = $inverseJoinColumn['onUpdate'];
                    }
                    
                    if (isset($joinColumn['onDelete'])) {
                        $fkOptions['onDelete'] = $inverseJoinColumn['onDelete'];
                    }
                }

                foreach($uniqueConstraints AS $unique) {
                    $theJoinTable->addUniqueIndex($unique);
                }

                // Build second FK constraint (relation table => target table)
                $theJoinTable->addForeignKeyConstraint(
                    $foreignClass->getQuotedTableName($this->_platform), $localColumns, $foreignColumns, null, $fkOptions
                );

                $theJoinTable->setPrimaryKey($primaryKeyColumns);
            }
        }
    }
    
    /**
     * Drops the database schema for the given classes.
     *
     * In any way when an exception is thrown it is supressed since drop was
     * issued for all classes of the schema and some probably just don't exist.
     *
     * @param array $classes
     * @param string $mode
     * @return void
     */
    public function dropSchema(array $classes, $mode=self::DROP_METADATA)
    {
        $dropSchemaSql = $this->getDropSchemaSql($classes, $mode);
        $conn = $this->_em->getConnection();
        
        foreach ($dropSchemaSql as $sql) {
            $conn->execute($sql);
        }
    }
    
    /**
     * Gets the SQL needed to drop the database schema for the given classes.
     * 
     * @param array $classes
     * @param string $mode
     * @return array
     */
    public function getDropSchemaSql(array $classes, $mode=self::DROP_METADATA)
    {
        if($mode == self::DROP_METADATA) {
            $tables = $this->_getDropSchemaTablesMetadataMode($classes);
        } else if($mode == self::DROP_DATABASE) {
            $tables = $this->_getDropSchemaTablesDatabaseMode($classes);
        } else {
            throw new \Doctrine\ORM\ORMException("Given Drop Schema Mode is not supported.");
        }

        $sm = $this->_em->getConnection()->getSchemaManager();
        /* @var $sm \Doctrine\DBAL\Schema\AbstractSchemaManager */
        $allTables = $sm->listTables();
        
        $sql = array();
        foreach($tables AS $tableName) {
            if(in_array($tableName, $allTables)) {
                $sql[] = $this->_platform->getDropTableSql($tableName);
            }
        }

        return $sql;
    }

    /**
     * Drop all tables of the database connection.
     * 
     * @return array
     */
    private function _getDropSchemaTablesDatabaseMode($classes)
    {
        $conn = $this->_em->getConnection();
        
        $sm = $conn->getSchemaManager();
        /* @var $sm \Doctrine\DBAL\Schema\AbstractSchemaManager */

        $allTables = $sm->listTables();

        $orderedTables = $this->_getDropSchemaTablesMetadataMode($classes);
        foreach($allTables AS $tableName) {
            if(!in_array($tableName, $orderedTables)) {
                $orderedTables[] = $tableName;
            }
        }

        return $orderedTables;
    }

    private function _getDropSchemaTablesMetadataMode(array $classes)
    {
        $orderedTables = array();
        
        $commitOrder = $this->_getCommitOrder($classes);
        $associationTables = $this->_getAssociationTables($commitOrder);

        // Drop association tables first
        foreach ($associationTables as $associationTable) {
            $orderedTables[] = $associationTable;
        }

        // Drop tables in reverse commit order
        for ($i = count($commitOrder) - 1; $i >= 0; --$i) {
            $class = $commitOrder[$i];

            if (($class->isInheritanceTypeSingleTable() && $class->name != $class->rootEntityName)
                || $class->isMappedSuperclass) {
                continue;
            }

            $orderedTables[] = $class->getTableName();
        }

        //TODO: Drop other schema elements, like sequences etc.

        return $orderedTables;
    }
    
    /**
     * Updates the database schema of the given classes by comparing the ClassMetadata
     * instances to the current database schema that is inspected.
     * 
     * @param array $classes
     * @return void
     */
    public function updateSchema(array $classes)
    {
        $updateSchemaSql = $this->getUpdateSchemaSql($classes);
        $conn = $this->_em->getConnection();
        
        foreach ($updateSchemaSql as $sql) {
            $conn->execute($sql);
        }
    }
    
    /**
     * Gets the sequence of SQL statements that need to be performed in order
     * to bring the given class mappings in-synch with the relational schema.
     * 
     * @param array $classes The classes to consider.
     * @return array The sequence of SQL statements.
     */
    public function getUpdateSchemaSql(array $classes)
    {
        $sql = array();
        $conn = $this->_em->getConnection();
        $sm = $conn->getSchemaManager();
        
        $tables = $sm->listTables();
        $newClasses = array();
        
        foreach ($classes as $class) {
            $tableName = $class->getTableName();
            $tableExists = false;
            
            foreach ($tables as $index => $table) {
                if ($tableName == $table) {
                    $tableExists = true;
                    
                    unset($tables[$index]);
                    break;
                }
            }
            
            if ( ! $tableExists) {
                $newClasses[] = $class;
            } else {
                $newFields = array();
                $updateFields = array();
                $dropIndexes = array();
                $newJoinColumns = array();
                $currentColumns = $sm->listTableColumns($tableName);
                                
                foreach ($class->fieldMappings as $fieldName => $fieldMapping) {
                    $exists = false;
                    
                    foreach ($currentColumns as $index => $column) {
                        if ($column['name'] == $fieldMapping['columnName']) {
                            // Column exists, check for changes
                            $columnInfo = $column;
                            $columnChanged = false;
                                                        
                            // 1. check for nullability change
                            $columnInfo['notnull'] = ( ! isset($columnInfo['notnull'])) 
                                ? false : $columnInfo['notnull'];
                            $notnull = ! $class->isNullable($fieldName);
                            
                            if ($columnInfo['notnull'] != $notnull) {
                                $columnInfo['notnull'] = $notnull;
                                $columnChanged = true;
                            }
                            
                            unset($notnull);
                            
                            // 2. check for uniqueness change
                            $columnInfo['unique'] = ( ! isset($columnInfo['unique'])) 
                                ? false : $columnInfo['unique'];
                            $unique = $class->isUniqueField($fieldName);
                            
                            if ($columnInfo['unique'] != $unique) {
                                // We need to call a special DROP INDEX if it was defined
                                if ($columnInfo['unique']) {
                                    $dropIndexes[] = $column['name'];
                                }
                                
                                $columnInfo['unique'] = $unique;
                                $columnChanged = true;
                            }
                            
                            unset($unique);
                            
                            // 3. check for type change
                            $type = Type::getType($fieldMapping['type']);
                            
                            if ($columnInfo['type'] != $type) {
                                $columnInfo['type'] = $type;
                                $columnChanged = true;
                            }
                            
                            unset($type);
                            
                            // 4. check for scale and precision change
                            if (strtolower($columnInfo['type']) == 'decimal') {
                                /*// Doesn't work yet, see DDC-89
                                if($columnInfo['length'] != $fieldMapping['precision'] ||
                                   $columnInfo['scale'] != $fieldMapping['scale']) {

                                    $columnInfo['length'] = $fieldMapping['precision'];
                                    $columnInfo['scale'] = $fieldMapping['scale'];
                                    $columnChanged = true;
                                }*/
                            }
                            // 5. check for length change of strings
                            elseif(strtolower($fieldMapping['type']) == 'string') {
                                if(!isset($fieldMapping['length']) || $fieldMapping['length'] === null) {
                                    $fieldMapping['length'] = 255;
                                }

                                if($columnInfo['length'] != $fieldMapping['length']) {
                                    $columnInfo['length'] = $fieldMapping['length'];
                                    $columnChanged = true;
                                }
                            }
                            
                            // 6. check for flexible and fixed length
                            $fieldMapping['fixed'] = ( ! isset($fieldMapping['fixed'])) 
                                ? false : $fieldMapping['fixed'];
                                
                            if ($columnInfo['fixed'] != $fieldMapping['fixed']) {
                                $columnInfo['fixed'] = $fieldMapping['fixed'];
                                $columnChanged = true;
                            }
                            
                            // Only add to column changed list if it was actually changed
                            if ($columnChanged) {
                                $updateFields[] = $columnInfo;
                            }
                            
                            unset($currentColumns[$index]);
                            $exists = true;
                            break;
                        }
                    }
                    
                    if ( ! $exists) {
                        $newFields[] = $fieldMapping;
                    }
                }
                
                foreach ($class->associationMappings as $assoc) {
                    if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                        foreach ($assoc->targetToSourceKeyColumns as $targetColumn => $sourceColumn) {
                            $exists = false;
                            
                            foreach ($currentColumns as $index => $column) {
                                if ($column['name'] == $sourceColumn) {
                                    // Column exists, check for changes
                                    
                                    // 1. check for nullability change
                                    
                                    unset($currentColumns[$index]);
                                    $exists = true;
                                    break;
                                }
                            }
                            
                            if ( ! $exists) {
                                $newJoinColumns[$sourceColumn] = array(
                                    'name' => $sourceColumn,
                                    'type' => 'integer' //FIXME!!!
                                );
                            }
                        }
                    }
                }
                
                // Drop indexes
                if ($dropIndexes) {
                    foreach ($dropIndexes as $dropIndex) {
                        $sql[] = $this->_platform->getDropIndexSql($tableName, $dropIndex);
                    }
                }
                
                // Create new columns
                if ($newFields || $newJoinColumns) {
                    $changes = array();
                    
                    foreach ($newFields as $newField) {
                        $options = array();
                        $table = new \Doctrine\DBAL\Schema\Table("foo");
                        $changes['add'][$newField['columnName']] = $this->_gatherUpdateColumn($class, $newField, $options, $table);
                    }
                    
                    foreach ($newJoinColumns as $name => $joinColumn) {
                        $joinColumn['type'] = Type::getType($joinColumn['type']);
                        $changes['add'][$name] = $joinColumn;
                    }
                    $sql = array_merge($sql, $this->_platform->getAlterTableSql($tableName, $changes));
                }
                
                // Update existent columns
                if ($updateFields) {
                    $changes = array();
                    
                    foreach ($updateFields as $updateField) {
                        // Now we pick the Type instance
                        $changes['change'][$updateField['name']] = array(
                            'definition' => $updateField
                        );
                    }
                    
                    $sql = array_merge($sql, $this->_platform->getAlterTableSql($tableName, $changes));
                }
                
                // Drop any remaining columns
                if ($currentColumns) {
                    $changes = array();
                    
                    foreach ($currentColumns as $column) {
                        $options = array();
                        $changes['remove'][$column['name']] = $column;
                    }
                    
                    $sql = array_merge($sql, $this->_platform->getAlterTableSql($tableName, $changes));
                }
            }
        }
        
        if ($newClasses) {
            $sql = array_merge($this->getCreateSchemaSql($newClasses), $sql);
        }
        
        // Drop any remaining tables (Probably not a good idea, because the given class list
        // may not be complete!)
        /*if ($tables) {
            foreach ($tables as $table) {
                $sql[] = $this->_platform->getDropTableSql($table);
            }
        }*/
        
        return $sql;
    }

    /**
     * Temporary method, required because schema update is not on par with schema create refactorings.
     * 
     * @param <type> $class
     * @param array $mapping
     * @param array $options
     * @param <type> $table
     * @return <type>
     */
    private function _gatherUpdateColumn($class, array $mapping, array &$options, $table)
    {
        $column = array();
        $column['name'] = $class->getQuotedColumnName($mapping['fieldName'], $this->_platform);
        $column['type'] = Type::getType($mapping['type']);
        $column['length'] = isset($mapping['length']) ? $mapping['length'] : null;
        $column['notnull'] = isset($mapping['nullable']) ? ! $mapping['nullable'] : true;
        $column['unique'] = isset($mapping['unique']) ? $mapping['unique'] : false;
        $column['version'] = $class->isVersioned && $class->versionField == $mapping['fieldName'] ? true : false;

        if(strtolower($column['type']) == 'string' && $column['length'] === null) {
            $column['length'] = 255;
        }

        if (isset($mapping['precision'])) {
            $column['precision'] = $mapping['precision'];
        }

        if (isset($mapping['scale'])) {
            $column['scale'] = $mapping['scale'];
        }

        if (isset($mapping['default'])) {
            $column['default'] = $mapping['default'];
        }

        if ($class->isIdentifier($mapping['fieldName'])) {
            $column['primary'] = true;
            $options['primary'][] = $mapping['columnName'];

            if ($class->isIdGeneratorIdentity()) {
                $column['autoincrement'] = true;
            }
        }

        return $column;
    }
    
    private function _getCommitOrder(array $classes)
    {
        $calc = new CommitOrderCalculator;
        
        // Calculate dependencies
        foreach ($classes as $class) {
            $calc->addClass($class);
            
            foreach ($class->associationMappings as $assoc) {
                if ($assoc->isOwningSide) {
                    $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);
                    
                    if ( ! $calc->hasClass($targetClass->name)) {
                        $calc->addClass($targetClass);
                    }
                    
                    // add dependency ($targetClass before $class)
                    $calc->addDependency($targetClass, $class);
                }
            }
        }

        return $calc->getCommitOrder();
    }
    
    private function _getAssociationTables(array $classes)
    {
        $associationTables = array();
        
        foreach ($classes as $class) {
            foreach ($class->associationMappings as $assoc) {
                if ($assoc->isOwningSide && $assoc->isManyToMany()) {
                    $associationTables[] = $assoc->joinTable['name'];
                }
            }
        }
        
        return $associationTables;
    }
}
