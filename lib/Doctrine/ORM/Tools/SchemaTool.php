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
        $processedClasses = array();
        $sql = array();
        $foreignKeyConstraints = array();

        // First we create the tables
        foreach ($classes as $class) {
            if (isset($processedClasses[$class->getClassName()])) {
                continue;
            }

            $columns = $this->_gatherColumns($class); // table columns
            $options = array(); // table options

            foreach ($class->getAssociationMappings() as $mapping) {
                $foreignClass = $this->_em->getClassMetadata($mapping->getTargetEntityName());
                if ($mapping->isOneToOne() && $mapping->isOwningSide()) {
                    $constraint = array();
                    $constraint['tableName'] = $class->getTableName();
                    $constraint['foreignTable'] = $foreignClass->getTableName();
                    $constraint['local'] = array();
                    $constraint['foreign'] = array();
                    foreach ($mapping->getJoinColumns() as $joinColumn) {
                        $column = array();
                        $column['name'] = $joinColumn['name'];
                        $column['type'] = $foreignClass->getTypeOfColumn($joinColumn['referencedColumnName']);
                        $columns[$joinColumn['name']] = $column;
                        $constraint['local'][] = $joinColumn['name'];
                        $constraint['foreign'][] = $joinColumn['referencedColumnName'];
                    }
                    $foreignKeyConstraints[] = $constraint;
                } else if ($mapping->isOneToMany() && $mapping->isOwningSide()) {
                    //... create join table, one-many through join table supported later
                    throw DoctrineException::updateMe("Not yet implemented.");
                } else if ($mapping->isManyToMany() && $mapping->isOwningSide()) {
                    // create join table
                    $joinTableColumns = array();
                    $joinTableOptions = array();
                    $joinTable = $mapping->getJoinTable();
                    $constraint1 = array();
                    $constraint1['tableName'] = $joinTable['name'];
                    $constraint1['foreignTable'] = $class->getTableName();
                    $constraint1['local'] = array();
                    $constraint1['foreign'] = array();
                    foreach ($joinTable['joinColumns'] as $joinColumn) {
                        $column = array();
                        $column['primary'] = true;
                        $joinTableOptions['primary'][] = $joinColumn['name'];
                        $column['name'] = $joinColumn['name'];
                        $column['type'] = $class->getTypeOfColumn($joinColumn['referencedColumnName']);
                        $joinTableColumns[$joinColumn['name']] = $column;
                        $constraint1['local'][] = $joinColumn['name'];
                        $constraint1['foreign'][] = $joinColumn['referencedColumnName'];
                    }
                    $foreignKeyConstraints[] = $constraint1;

                    $constraint2 = array();
                    $constraint2['tableName'] = $joinTable['name'];
                    $constraint2['foreignTable'] = $foreignClass->getTableName();
                    $constraint2['local'] = array();
                    $constraint2['foreign'] = array();
                    foreach ($joinTable['inverseJoinColumns'] as $inverseJoinColumn) {
                        $column = array();
                        $column['primary'] = true;
                        $joinTableOptions['primary'][] = $inverseJoinColumn['name'];
                        $column['name'] = $inverseJoinColumn['name'];
                        $column['type'] = $this->_em->getClassMetadata($mapping->getTargetEntityName())
                                ->getTypeOfColumn($inverseJoinColumn['referencedColumnName']);
                        $joinTableColumns[$inverseJoinColumn['name']] = $column;
                        $constraint2['local'][] = $inverseJoinColumn['name'];
                        $constraint2['foreign'][] = $inverseJoinColumn['referencedColumnName'];
                    }
                    $foreignKeyConstraints[] = $constraint2;

                    $sql = array_merge($sql, $this->_platform->getCreateTableSql(
                            $joinTable['name'], $joinTableColumns, $joinTableOptions));
                }
            }

            if ($class->isInheritanceTypeSingleTable()) {
                // Add the discriminator column
                $discrColumnDef = $this->_getDiscriminatorColumnDefinition($class);
                $columns[$discrColumnDef['name']] = $discrColumnDef;

                // Aggregate all the information from all classes in the hierarchy
                foreach ($class->getParentClasses() as $parentClassName) {
                    // Parent class information is already contained in this class
                    $processedClasses[$parentClassName] = true;
                }
                foreach ($class->getSubclasses() as $subClassName) {
                    $columns = array_merge($columns, $this->_gatherColumns($this->_em->getClassMetadata($subClassName)));
                    $processedClasses[$subClassName] = true;
                }
            } else if ($class->isInheritanceTypeJoined()) {
                //TODO
            } else if ($class->isInheritanceTypeTablePerClass()) {
                //TODO
            }

            $sql = array_merge($sql, $this->_platform->getCreateTableSql($class->getTableName(), $columns, $options));
            $processedClasses[$class->getClassName()] = true;
        }

        // Now create the foreign key constraints
        if ($this->_platform->supportsForeignKeyConstraints()) {
            foreach ($foreignKeyConstraints as $fkConstraint) {
                $sql = array_merge($sql, (array)$this->_platform->getCreateForeignKeySql($fkConstraint['tableName'], $fkConstraint));
            }
        }

        return $sql;
    }

    private function _getDiscriminatorColumnDefinition($class)
    {
        $discrColumn = $class->getDiscriminatorColumn();
        return array(
            'name' => $discrColumn['name'],
            'type' => Type::getType($discrColumn['type']),
            'length' => $discrColumn['length'],
            'notnull' => true
        );
    }

    private function _gatherColumns($class)
    {
        $columns = array();
        foreach ($class->getFieldMappings() as $fieldName => $mapping) {
            $column = array();
            $column['name'] = $mapping['columnName'];
            $column['type'] = $mapping['type'];
            $column['length'] = $mapping['length'];
            $column['notnull'] = ! $mapping['nullable'];
            if ($class->isIdentifier($fieldName)) {
                $column['primary'] = true;
                $options['primary'][] = $mapping['columnName'];
                if ($class->isIdGeneratorIdentity()) {
                    $column['autoincrement'] = true;
                }
            }
            $columns[$mapping['columnName']] = $column;
        }
        return $columns;
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