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

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Cache\ArrayCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\DBAL\Schema\AbstractSchemaManager,
    Doctrine\DBAL\Schema\SchemaException,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Mapping\MappingException,
    Doctrine\Common\Util\Inflector;

/**
 * The DatabaseDriver reverse engineers the mapping metadata from a database.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class DatabaseDriver implements Driver
{
    /**
     * @var AbstractSchemaManager
     */
    private $_sm;

    /**
     * @var array
     */
    private $tables = null;

    private $classToTableNames = array();

    /**
     * @var array
     */
    private $manyToManyTables = array();

    /**
     * @var array
     */
    private $classNamesForTables = array();

    /**
     * @var array
     */
    private $fieldNamesForColumns = array();

    /**
     * The namespace for the generated entities.
     *
     * @var string
     */
    private $namespace;

    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     *
     * @param AnnotationReader $reader The AnnotationReader to use.
     */
    public function __construct(AbstractSchemaManager $schemaManager)
    {
        $this->_sm = $schemaManager;
    }

    /**
     * Set tables manually instead of relying on the reverse engeneering capabilities of SchemaManager.
     *
     * @param array $entityTables
     * @param array $manyToManyTables
     * @return void
     */
    public function setTables($entityTables, $manyToManyTables)
    {
        $this->tables = $this->manyToManyTables = $this->classToTableNames = array();
        foreach ($entityTables AS $table) {
            $className = $this->getClassNameForTable($table->getName());
            $this->classToTableNames[$className] = $table->getName();
            $this->tables[$table->getName()] = $table;
        }
        foreach ($manyToManyTables AS $table) {
            $this->manyToManyTables[$table->getName()] = $table;
        }
    }

    private function reverseEngineerMappingFromDatabase()
    {
        if ($this->tables !== null) {
            return;
        }

        $tables = array();

        foreach ($this->_sm->listTableNames() as $tableName) {
            $tables[$tableName] = $this->_sm->listTableDetails($tableName);
        }

        $this->tables = $this->manyToManyTables = $this->classToTableNames = array();
        foreach ($tables AS $tableName => $table) {
            /* @var $table Table */
            if ($this->_sm->getDatabasePlatform()->supportsForeignKeyConstraints()) {
                $foreignKeys = $table->getForeignKeys();
            } else {
                $foreignKeys = array();
            }

            $allForeignKeyColumns = array();
            foreach ($foreignKeys AS $foreignKey) {
                $allForeignKeyColumns = array_merge($allForeignKeyColumns, $foreignKey->getLocalColumns());
            }

            if ( ! $table->hasPrimaryKey()) {
                throw new MappingException(
                    "Table " . $table->getName() . " has no primary key. Doctrine does not ".
                    "support reverse engineering from tables that don't have a primary key."
                );
            }

            $pkColumns = $table->getPrimaryKey()->getColumns();
            sort($pkColumns);
            sort($allForeignKeyColumns);

            if ($pkColumns == $allForeignKeyColumns && count($foreignKeys) == 2) {
                $this->manyToManyTables[$tableName] = $table;
            } else {
                // lower-casing is necessary because of Oracle Uppercase Tablenames,
                // assumption is lower-case + underscore separated.
                $className = $this->getClassNameForTable($tableName);
                $this->tables[$tableName] = $table;
                $this->classToTableNames[$className] = $tableName;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadataInfo $metadata)
    {
        $this->reverseEngineerMappingFromDatabase();

        if (!isset($this->classToTableNames[$className])) {
            throw new \InvalidArgumentException("Unknown class " . $className);
        }

        $tableName = $this->classToTableNames[$className];

        $metadata->name = $className;
        $metadata->table['name'] = $tableName;

        $columns = $this->tables[$tableName]->getColumns();
        $indexes = $this->tables[$tableName]->getIndexes();
        try {
            $primaryKeyColumns = $this->tables[$tableName]->getPrimaryKey()->getColumns();
        } catch(SchemaException $e) {
            $primaryKeyColumns = array();
        }

        if ($this->_sm->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $foreignKeys = $this->tables[$tableName]->getForeignKeys();
        } else {
            $foreignKeys = array();
        }

        $allForeignKeyColumns = array();
        foreach ($foreignKeys AS $foreignKey) {
            $allForeignKeyColumns = array_merge($allForeignKeyColumns, $foreignKey->getLocalColumns());
        }

        $ids = array();
        $fieldMappings = array();
        foreach ($columns as $column) {
            $fieldMapping = array();
            
            if (in_array($column->getName(), $allForeignKeyColumns)) {
                continue;
            } else if ($primaryKeyColumns && in_array($column->getName(), $primaryKeyColumns)) {
                $fieldMapping['id'] = true;
            }
            
            $fieldMapping['fieldName'] = $this->getFieldNameForColumn($tableName, $column->getName(), false);
            $fieldMapping['columnName'] = $column->getName();
            $fieldMapping['type'] = strtolower((string) $column->getType());

            if ($column->getType() instanceof \Doctrine\DBAL\Types\StringType) {
                $fieldMapping['length'] = $column->getLength();
                $fieldMapping['fixed'] = $column->getFixed();
            } else if ($column->getType() instanceof \Doctrine\DBAL\Types\IntegerType) {
                $fieldMapping['unsigned'] = $column->getUnsigned();
            }
            $fieldMapping['nullable'] = $column->getNotNull() ? false : true;

            if (isset($fieldMapping['id'])) {
                $ids[] = $fieldMapping;
            } else {
                $fieldMappings[] = $fieldMapping;
            }
        }

        if ($ids) {
            if (count($ids) == 1) {
                $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
            }

            foreach ($ids as $id) {
                $metadata->mapField($id);
            }
        }

        foreach ($fieldMappings as $fieldMapping) {
            $metadata->mapField($fieldMapping);
        }

        foreach ($this->manyToManyTables AS $manyTable) {
            foreach ($manyTable->getForeignKeys() AS $foreignKey) {
                // foreign  key maps to the table of the current entity, many to many association probably exists
                if (strtolower($tableName) == strtolower($foreignKey->getForeignTableName())) {
                    $myFk = $foreignKey;
                    $otherFk = null;
                    foreach ($manyTable->getForeignKeys() AS $foreignKey) {
                        if ($foreignKey != $myFk) {
                            $otherFk = $foreignKey;
                            break;
                        }
                    }

                    if (!$otherFk) {
                        // the definition of this many to many table does not contain
                        // enough foreign key information to continue reverse engeneering.
                        continue;
                    }

                    $localColumn = current($myFk->getColumns());
                    $associationMapping = array();
                    $associationMapping['fieldName'] = $this->getFieldNameForColumn($manyTable->getName(), current($otherFk->getColumns()), true);
                    $associationMapping['targetEntity'] = $this->getClassNameForTable($otherFk->getForeignTableName());
                    if (current($manyTable->getColumns())->getName() == $localColumn) {
                        $associationMapping['inversedBy'] = $this->getFieldNameForColumn($manyTable->getName(), current($myFk->getColumns()), true);
                        $associationMapping['joinTable'] = array(
                            'name' => strtolower($manyTable->getName()),
                            'joinColumns' => array(),
                            'inverseJoinColumns' => array(),
                        );

                        $fkCols = $myFk->getForeignColumns();
                        $cols = $myFk->getColumns();
                        for ($i = 0; $i < count($cols); $i++) {
                            $associationMapping['joinTable']['joinColumns'][] = array(
                                'name' => $cols[$i],
                                'referencedColumnName' => $fkCols[$i],
                            );
                        }

                        $fkCols = $otherFk->getForeignColumns();
                        $cols = $otherFk->getColumns();
                        for ($i = 0; $i < count($cols); $i++) {
                            $associationMapping['joinTable']['inverseJoinColumns'][] = array(
                                'name' => $cols[$i],
                                'referencedColumnName' => $fkCols[$i],
                            );
                        }
                    } else {
                        $associationMapping['mappedBy'] = $this->getFieldNameForColumn($manyTable->getName(), current($myFk->getColumns()), true);
                    }
                    $metadata->mapManyToMany($associationMapping);
                    break;
                }
            }
        }

        foreach ($foreignKeys as $foreignKey) {
            $foreignTable = $foreignKey->getForeignTableName();
            $cols = $foreignKey->getColumns();
            $fkCols = $foreignKey->getForeignColumns();

            $localColumn = current($cols);
            $associationMapping = array();
            $associationMapping['fieldName'] = $this->getFieldNameForColumn($tableName, $localColumn, true);
            $associationMapping['targetEntity'] = $this->getClassNameForTable($foreignTable);

            if ($primaryKeyColumns && in_array($localColumn, $primaryKeyColumns)) {
                $associationMapping['id'] = true;
            }

            for ($i = 0; $i < count($cols); $i++) {
                $associationMapping['joinColumns'][] = array(
                    'name' => $cols[$i],
                    'referencedColumnName' => $fkCols[$i],
                );
            }
            
            //Here we need to check if $cols are the same as $primaryKeyColums
            if (!array_diff($cols,$primaryKeyColumns)) {
                $metadata->mapOneToOne($associationMapping);
            } else {
                $metadata->mapManyToOne($associationMapping);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        return true;
    }

    /**
     * Return all the class names supported by this driver.
     *
     * IMPORTANT: This method must return an array of class not tables names.
     *
     * @return array
     */
    public function getAllClassNames()
    {
        $this->reverseEngineerMappingFromDatabase();

        return array_keys($this->classToTableNames);
    }

    /**
     * Set class name for a table.
     *
     * @param string $tableName
     * @param string $className
     * @return void
     */
    public function setClassNameForTable($tableName, $className)
    {
        $this->classNamesForTables[$tableName] = $className;
    }

    /**
     * Set field name for a column on a specific table.
     *
     * @param string $tableName
     * @param string $columnName
     * @param string $fieldName
     * @return void
     */
    public function setFieldNameForColumn($tableName, $columnName, $fieldName)
    {
        $this->fieldNamesForColumns[$tableName][$columnName] = $fieldName;
    }

    /**
     * Return the mapped class name for a table if it exists. Otherwise return "classified" version.
     *
     * @param string $tableName
     * @return string
     */
    private function getClassNameForTable($tableName)
    {
        if (isset($this->classNamesForTables[$tableName])) {
            return $this->namespace . $this->classNamesForTables[$tableName];
        }

        return $this->namespace . Inflector::classify(strtolower($tableName));
    }

    /**
     * Return the mapped field name for a column, if it exists. Otherwise return camelized version.
     *
     * @param string $tableName
     * @param string $columnName
     * @param boolean $fk Whether the column is a foreignkey or not.
     * @return string
     */
    private function getFieldNameForColumn($tableName, $columnName, $fk = false)
    {
        if (isset($this->fieldNamesForColumns[$tableName]) && isset($this->fieldNamesForColumns[$tableName][$columnName])) {
            return $this->fieldNamesForColumns[$tableName][$columnName];
        }

        $columnName = strtolower($columnName);

        // Replace _id if it is a foreignkey column
        if ($fk) {
            $columnName = str_replace('_id', '', $columnName);
        }
        return Inflector::camelize($columnName);
    }

    /**
     * Set the namespace for the generated entities.
     *
     * @param string $namespace
     * @return void
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }
}
