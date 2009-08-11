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

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;

/**
 * The SchemaTool is a tool to create and/or drop database schemas based on
 * <tt>ClassMetadata</tt> class descriptors.
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision: 4805 $
 */
class SchemaTool
{
    /** The EntityManager */
    private $_em;
    /** The DatabasePlatform */
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
     * Gets an array of DDL statements for the specified array of ClassMetadata instances.
     *
     * @param array $classes
     * @return array $sql
     */
    public function getCreateSchemaSql(array $classes)
    {
        $sql = array(); // All SQL statements
        $processedClasses = array(); // Reminder for processed classes, used for hierarchies
        $foreignKeyConstraints = array(); // FK SQL statements. Appended to $sql at the end.
        $sequences = array(); // Sequence SQL statements. Appended to $sql at the end.

        foreach ($classes as $class) {
            if (isset($processedClasses[$class->name])) {
                continue;
            }

            $options = array(); // table options
            $columns = array(); // table columns
            
            if ($class->isInheritanceTypeSingleTable()) {
                $columns = $this->_gatherColumns($class, $options);
                $this->_gatherRelationsSql($class, $sql, $columns, $foreignKeyConstraints);
                
                // Add the discriminator column
                $discrColumnDef = $this->_getDiscriminatorColumnDefinition($class);
                $columns[$discrColumnDef['name']] = $discrColumnDef;

                // Aggregate all the information from all classes in the hierarchy
                foreach ($class->parentClasses as $parentClassName) {
                    // Parent class information is already contained in this class
                    $processedClasses[$parentClassName] = true;
                }
                foreach ($class->subClasses as $subClassName) {
                    $subClass = $this->_em->getClassMetadata($subClassName);
                    $columns = array_merge($columns, $this->_gatherColumns($subClass, $options));
                    $this->_gatherRelationsSql($subClass, $sql, $columns, $foreignKeyConstraints);
                    $processedClasses[$subClassName] = true;
                }
            } else if ($class->isInheritanceTypeJoined()) {
                // Add all non-inherited fields as columns
                foreach ($class->fieldMappings as $fieldName => $mapping) {
                    if ( ! isset($mapping['inherited'])) {
                        $columnName = $class->getQuotedColumnName($mapping['columnName'], $this->_platform);
                        $columns[$columnName] = $this->_gatherColumn($class, $mapping, $options);
                    }
                }

                $this->_gatherRelationsSql($class, $sql, $columns, $foreignKeyConstraints);

                // Add the discriminator column only to the root table
                if ($class->name == $class->rootEntityName) {
                    $discrColumnDef = $this->_getDiscriminatorColumnDefinition($class);
                    $columns[$discrColumnDef['name']] = $discrColumnDef;
                } else {
                    // Add an ID FK column to child tables
                    $idMapping = $class->fieldMappings[$class->identifier[0]];
                    $idColumn = $this->_gatherColumn($class, $idMapping, $options);
                    unset($idColumn['autoincrement']);
                    $columns[$idColumn['name']] = $idColumn;
                    // Add a FK constraint on the ID column
                    $constraint = array();
                    $constraint['tableName'] = $class->getQuotedTableName($this->_platform);
                    $constraint['foreignTable'] = $this->_em->getClassMetadata($class->rootEntityName)->getQuotedTableName($this->_platform);
                    $constraint['local'] = array($idColumn['name']);
                    $constraint['foreign'] = array($idColumn['name']);
                    $constraint['onDelete'] = 'CASCADE';
                    $foreignKeyConstraints[] = $constraint;
                }
            } else if ($class->isInheritanceTypeTablePerClass()) {
                //TODO
            } else {
                $columns = $this->_gatherColumns($class, $options);
                $this->_gatherRelationsSql($class, $sql, $columns, $foreignKeyConstraints);
            }

            $sql = array_merge($sql, $this->_platform->getCreateTableSql(
                    $class->getQuotedTableName($this->_platform), $columns, $options));
            $processedClasses[$class->name] = true;

            if ($class->isIdGeneratorSequence() && $class->name == $class->rootEntityName) {
                $seqDef = $class->getSequenceGeneratorDefinition();
                $sequences[] = $this->_platform->getCreateSequenceSql(
                    $seqDef['sequenceName'],
                    $seqDef['initialValue'],
                    $seqDef['allocationSize']
                );
            }
        }

        // Append the foreign key constraints SQL
        if ($this->_platform->supportsForeignKeyConstraints()) {
            foreach ($foreignKeyConstraints as $fkConstraint) {
                $sql = array_merge($sql, (array) $this->_platform->getCreateForeignKeySql($fkConstraint['tableName'], $fkConstraint));
            }
        }

        // Append the sequence SQL
        $sql = array_merge($sql, $sequences);

        return $sql;
    }

    private function _getDiscriminatorColumnDefinition($class)
    {
        $discrColumn = $class->discriminatorColumn;
        return array(
            'name' => $class->getQuotedDiscriminatorColumnName($this->_platform),
            'type' => Type::getType($discrColumn['type']),
            'length' => $discrColumn['length'],
            'notnull' => true
        );
    }

    /**
     * Gathers the column definitions of all field mappings found in the given class.
     *
     * @param ClassMetadata $class
     * @param array $options
     * @return array
     */
    private function _gatherColumns($class, array &$options)
    {
        $columns = array();
        foreach ($class->fieldMappings as $fieldName => $mapping) {
            $column = $this->_gatherColumn($class, $mapping, $options);
            $columns[$column['name']] = $column;
        }
        
        return $columns;
    }

    private function _gatherColumn($class, array $mapping, array &$options)
    {
        $column = array();
        $column['name'] = $class->getQuotedColumnName($mapping['columnName'], $this->_platform);
        $column['type'] = Type::getType($mapping['type']);
        $column['length'] = $mapping['length'];
        $column['notnull'] = ! $mapping['nullable'];
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

    private function _gatherRelationsSql($class, array &$sql, array &$columns, array &$constraints)
    {
        foreach ($class->associationMappings as $fieldName => $mapping) {
            if (isset($class->inheritedAssociationFields[$fieldName])) {
                continue;
            }

            $foreignClass = $this->_em->getClassMetadata($mapping->targetEntityName);
            if ($mapping->isOneToOne() && $mapping->isOwningSide) {
                $constraint = array();
                $constraint['tableName'] = $class->getQuotedTableName($this->_platform);
                $constraint['foreignTable'] = $foreignClass->getQuotedTableName($this->_platform);
                $constraint['local'] = array();
                $constraint['foreign'] = array();
                foreach ($mapping->getJoinColumns() as $joinColumn) {
                    $column = array();
                    $column['name'] = $mapping->getQuotedJoinColumnName($joinColumn['name'], $this->_platform);
                    $column['type'] = Type::getType($foreignClass->getTypeOfColumn($joinColumn['referencedColumnName']));
                    $columns[$column['name']] = $column;
                    $constraint['local'][] = $column['name'];
                    $constraint['foreign'][] = $joinColumn['referencedColumnName'];
                }
                $constraints[] = $constraint;
            } else if ($mapping->isOneToMany() && $mapping->isOwningSide) {
                //... create join table, one-many through join table supported later
                throw DoctrineException::updateMe("Not yet implemented.");
            } else if ($mapping->isManyToMany() && $mapping->isOwningSide) {
                // create join table
                $joinTableColumns = array();
                $joinTableOptions = array();
                $joinTable = $mapping->getJoinTable();
                $constraint1 = array(
                    'tableName' => $mapping->getQuotedJoinTableName($this->_platform),
                    'foreignTable' => $class->getQuotedTableName($this->_platform),
                    'local' => array(),
                    'foreign' => array()
                );
                foreach ($joinTable['joinColumns'] as $joinColumn) {
                    $column = array();
                    $column['primary'] = true;
                    $joinTableOptions['primary'][] = $joinColumn['name'];
                    $column['name'] = $mapping->getQuotedJoinColumnName($joinColumn['name'], $this->_platform);
                    $column['type'] = Type::getType($class->getTypeOfColumn($joinColumn['referencedColumnName']));
                    $joinTableColumns[$column['name']] = $column;
                    $constraint1['local'][] = $column['name'];
                    $constraint1['foreign'][] = $joinColumn['referencedColumnName'];
                }
                $constraints[] = $constraint1;

                $constraint2 = array();
                $constraint2['tableName'] = $mapping->getQuotedJoinTableName($this->_platform);
                $constraint2['foreignTable'] = $foreignClass->getQuotedTableName($this->_platform);
                $constraint2['local'] = array();
                $constraint2['foreign'] = array();
                foreach ($joinTable['inverseJoinColumns'] as $inverseJoinColumn) {
                    $column = array();
                    $column['primary'] = true;
                    $joinTableOptions['primary'][] = $inverseJoinColumn['name'];
                    $column['name'] = $inverseJoinColumn['name'];
                    $column['type'] = Type::getType($this->_em->getClassMetadata($mapping->getTargetEntityName())
                            ->getTypeOfColumn($inverseJoinColumn['referencedColumnName']));
                    $joinTableColumns[$inverseJoinColumn['name']] = $column;
                    $constraint2['local'][] = $inverseJoinColumn['name'];
                    $constraint2['foreign'][] = $inverseJoinColumn['referencedColumnName'];
                }
                $constraints[] = $constraint2;

                $sql = array_merge($sql, $this->_platform->getCreateTableSql(
                        $mapping->getQuotedJoinTableName($this->_platform), $joinTableColumns, $joinTableOptions)
                        );
            }
        }
    }

    public function dropSchema(array $classes)
    {
        //TODO
    }

    public function getDropSchemaSql(array $classes)
    {
        //TODO
    }
}
