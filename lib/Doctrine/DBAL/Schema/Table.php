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

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Visitor\Visitor;
use Doctrine\DBAL\DBALException;

/**
 * Object Representation of a table
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class Table extends AbstractAsset
{
    /**
     * @var int
     */
    const ID_NONE = 0;

    /**
     * @var int
     */
    const ID_SEQUENCE = 1;

    /**
     * @var int
     */
    const ID_IDENTITY = 2;

    /**
     * @var string
     */
    protected $_name = null;

    /**
     * @var array
     */
    protected $_columns = array();

    /**
     * @var array
     */
    protected $_indexes = array();

    /**
     * @var string
     */
    protected $_primaryKeyName = false;

    /**
     * @var array
     */
    protected $_fkConstraints = array();

    /**
     * @var array
     */
    protected $_options = array();

    /**
     * @var bool
     */
    protected $_idGeneratorType = self::ID_NONE;

    /**
     * @var SchemaConfig
     */
    protected $_schemaConfig = null;

    /**
     *
     * @param string $tableName
     * @param array $columns
     * @param array $indexes
     * @param array $fkConstraints
     * @param int $idGeneratorType
     * @param array $options
     */
    public function __construct($tableName, array $columns=array(), array $indexes=array(), array $fkConstraints=array(), $idGeneratorType=self::ID_NONE, array $options=array())
    {
        if (strlen($tableName) == 0) {
            throw DBALException::invalidTableName($tableName);
        }

        $this->_setName($tableName);
        $this->_idGeneratorType = $idGeneratorType;
        
        foreach ($columns AS $column) {
            $this->_addColumn($column);
        }
        
        foreach ($indexes AS $idx) {
            $this->_addIndex($idx);
        }

        foreach ($fkConstraints AS $constraint) {
            $this->_addForeignKeyConstraint($constraint);
        }

        $this->_options = $options;
    }

    /**
     * @param SchemaConfig $schemaConfig
     */
    public function setSchemaConfig(SchemaConfig $schemaConfig)
    {
        $this->_schemaConfig = $schemaConfig;
    }

    /**
     * @return int
     */
    protected function _getMaxIdentifierLength()
    {
        if ($this->_schemaConfig instanceof SchemaConfig) {
            return $this->_schemaConfig->getMaxIdentifierLength();
        } else {
            return 63;
        }
    }

    /**
     * Set Primary Key
     *
     * @param array $columns
     * @param string $indexName
     * @return Table
     */
    public function setPrimaryKey(array $columns, $indexName = false)
    {
        $primaryKey = $this->_createIndex($columns, $indexName ?: "primary", true, true);

        foreach ($columns AS $columnName) {
            $column = $this->getColumn($columnName);
            $column->setNotnull(true);
        }

        return $primaryKey;
    }

    /**
     * @param string $type
     * @return Table
     */
    public function setIdGeneratorType($type)
    {
        $this->_idGeneratorType = $type;
        return $this;
    }

    /**
     * @param array $columnNames
     * @param string $indexName
     * @return Table
     */
    public function addIndex(array $columnNames, $indexName = null)
    {
        if($indexName == null) {
            $indexName = $this->_generateIdentifierName(
                array_merge(array($this->getName()), $columnNames), "idx", $this->_getMaxIdentifierLength()
            );
        }

        return $this->_createIndex($columnNames, $indexName, false, false);
    }

    /**
     *
     * @param array $columnNames
     * @param string $indexName
     * @return Table
     */
    public function addUniqueIndex(array $columnNames, $indexName = null)
    {
        if ($indexName == null) {
            $indexName = $this->_generateIdentifierName(
                array_merge(array($this->getName()), $columnNames), "uniq", $this->_getMaxIdentifierLength()
            );
        }

        return $this->_createIndex($columnNames, $indexName, true, false);
    }

    /**
     * Check if an index begins in the order of the given columns.
     *
     * @param  array $columnsNames
     * @return bool
     */
    public function columnsAreIndexed(array $columnsNames)
    {
        foreach ($this->getIndexes() AS $index) {
            $indexColumns = $index->getColumns();
            $areIndexed = true;
            for ($i = 0; $i < count($columnsNames); $i++) {
                if ($columnsNames[$i] != $indexColumns[$i]) {
                    $areIndexed = false;
                }
            }

            if ($areIndexed) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param array $columnNames
     * @param string $indexName
     * @param bool $isUnique
     * @param bool $isPrimary
     * @return Table
     */
    private function _createIndex(array $columnNames, $indexName, $isUnique, $isPrimary)
    {
        if (preg_match('(([^a-zA-Z0-9_]+))', $indexName)) {
            throw SchemaException::indexNameInvalid($indexName);
        }

        foreach ($columnNames AS $columnName => $indexColOptions) {
            if (is_numeric($columnName) && is_string($indexColOptions)) {
                $columnName = $indexColOptions;
            }

            if ( ! $this->hasColumn($columnName)) {
                throw SchemaException::columnDoesNotExist($columnName);
            }
        }
        $this->_addIndex(new Index($indexName, $columnNames, $isUnique, $isPrimary));
        return $this;
    }

    /**
     * @param string $columnName
     * @param string $columnType
     * @param array $options
     * @return Column
     */
    public function addColumn($columnName, $typeName, array $options=array())
    {
        $column = new Column($columnName, Type::getType($typeName), $options);

        $this->_addColumn($column);
        return $column;
    }

    /**
     * Rename Column
     *
     * @param string $oldColumnName
     * @param string $newColumnName
     * @return Table
     */
    public function renameColumn($oldColumnName, $newColumnName)
    {
        $column = $this->getColumn($oldColumnName);
        $this->dropColumn($oldColumnName);

        $column->_setName($newColumnName);
        return $this;
    }

    /**
     * Change Column Details
     * 
     * @param string $columnName
     * @param array $options
     * @return Table
     */
    public function changeColumn($columnName, array $options)
    {
        $column = $this->getColumn($columnName);
        $column->setOptions($options);
        return $this;
    }

    /**
     * Drop Column from Table
     * 
     * @param string $columnName
     * @return Table
     */
    public function dropColumn($columnName)
    {
        $columnName = strtolower($columnName);
        $column = $this->getColumn($columnName);
        unset($this->_columns[$columnName]);
        return $this;
    }


    /**
     * Add a foreign key constraint
     *
     * Name is inferred from the local columns
     *
     * @param Table $foreignTable
     * @param array $localColumns
     * @param array $foreignColumns
     * @param array $options
     * @return Table
     */
    public function addForeignKeyConstraint($foreignTable, array $localColumnNames, array $foreignColumnNames, array $options=array())
    {
        $name = $this->_generateIdentifierName(array_merge((array)$this->getName(), $localColumnNames), "fk", $this->_getMaxIdentifierLength());
        return $this->addNamedForeignKeyConstraint($name, $foreignTable, $localColumnNames, $foreignColumnNames, $options);
    }

    /**
     * Add a foreign key constraint
     *
     * Name is to be generated by the database itsself.
     *
     * @param Table $foreignTable
     * @param array $localColumns
     * @param array $foreignColumns
     * @param array $options
     * @return Table
     */
    public function addUnnamedForeignKeyConstraint($foreignTable, array $localColumnNames, array $foreignColumnNames, array $options=array())
    {
        return $this->addNamedForeignKeyConstraint(null, $foreignTable, $localColumnNames, $foreignColumnNames, $options);
    }

    /**
     * Add a foreign key constraint with a given name
     *
     * @param string $name
     * @param Table $foreignTable
     * @param array $localColumns
     * @param array $foreignColumns
     * @param array $options
     * @return Table
     */
    public function addNamedForeignKeyConstraint($name, $foreignTable, array $localColumnNames, array $foreignColumnNames, array $options=array())
    {
        if ($foreignTable instanceof Table) {
            $foreignTableName = $foreignTable->getName();

            foreach ($foreignColumnNames AS $columnName) {
                if ( ! $foreignTable->hasColumn($columnName)) {
                    throw SchemaException::columnDoesNotExist($columnName);
                }
            }
        } else {
            $foreignTableName = $foreignTable;
        }

        foreach ($localColumnNames AS $columnName) {
            if ( ! $this->hasColumn($columnName)) {
                throw SchemaException::columnDoesNotExist($columnName);
            }
        }
        
        $constraint = new ForeignKeyConstraint(
            $localColumnNames, $foreignTableName, $foreignColumnNames, $name, $options
        );
        $this->_addForeignKeyConstraint($constraint);

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return Table
     */
    public function addOption($name, $value)
    {
        $this->_options[$name] = $value;
        return $this;
    }

    /**
     * @param Column $column
     */
    protected function _addColumn(Column $column)
    {
        $columnName = $column->getName();
        $columnName = strtolower($columnName);

        if (isset($this->_columns[$columnName])) {
            throw SchemaException::columnAlreadyExists($this->getName(), $columnName);
        }

        $this->_columns[$columnName] = $column;
    }

    /**
     * Add index to table
     * 
     * @param Index $index
     * @return Table
     */
    protected function _addIndex(Index $index)
    {
        // check for duplicates
        $c = new Comparator();
        foreach ($this->_indexes AS $existingIndex) {
            if ($c->diffIndex($index, $existingIndex) == false) {
                return $this;
            }
        }

        $indexName = $index->getName();
        $indexName = strtolower($indexName);

        if (isset($this->_indexes[$indexName]) || ($this->_primaryKeyName != false && $index->isPrimary())) {
            throw SchemaException::indexAlreadyExists($indexName);
        }

        if ($index->isPrimary()) {
            $this->_primaryKeyName = $indexName;
        }

        $this->_indexes[$indexName] = $index;
        return $this;
    }

    /**
     * @param ForeignKeyConstraint $constraint
     */
    protected function _addForeignKeyConstraint(ForeignKeyConstraint $constraint)
    {
        $constraint->setLocalTable($this);
        
        if(strlen($constraint->getName())) {
            $name = $constraint->getName();
        } else {
            $name = $this->_generateIdentifierName(
                array_merge((array)$this->getName(), $constraint->getLocalColumns()), "fk", $this->_getMaxIdentifierLength()
            );
        }
        $name = strtolower($name);

        $this->_fkConstraints[$name] = $constraint;
    }

    /**
     * Does Table have a foreign key constraint with the given name?
     *      *
     * @param  string $constraintName
     * @return bool
     */
    public function hasForeignKey($constraintName)
    {
        $constraintName = strtolower($constraintName);
        return isset($this->_fkConstraints[$constraintName]);
    }

    /**
     * @param string $constraintName
     * @return ForeignKeyConstraint
     */
    public function getForeignKey($constraintName)
    {
        $constraintName = strtolower($constraintName);
        if(!$this->hasForeignKey($constraintName)) {
            throw SchemaException::foreignKeyDoesNotExist($constraintName);
        }

        return $this->_fkConstraints[$constraintName];
    }

    /**
     * @return bool
     */
    public function isIdGeneratorIdentity()
    {
        return ($this->_idGeneratorType==self::ID_IDENTITY);
    }

    /**
     * @return array
     */
    public function isIdGeneratorSequence()
    {
        return ($this->_idGeneratorType==self::ID_SEQUENCE);
    }

    /**
     * @return Column[]
     */
    public function getColumns()
    {
        $columns = $this->_columns;

        $pkCols = array();
        $fkCols = array();

        if ($this->hasIndex($this->_primaryKeyName)) {
            $pkCols = $this->getPrimaryKey()->getColumns();
        }
        foreach ($this->getForeignKeys() AS $fk) {
            /* @var $fk ForeignKeyConstraint */
            $fkCols = array_merge($fkCols, $fk->getColumns());
        }
        $colNames = array_unique(array_merge($pkCols, $fkCols, array_keys($columns)));

        uksort($columns, function($a, $b) use($colNames) {
            return (array_search($a, $colNames) >= array_search($b, $colNames));
        });
        return $columns;
    }


    /**
     * Does this table have a column with the given name?
     *
     * @param  string $columnName
     * @return bool
     */
    public function hasColumn($columnName)
    {
        $columnName = strtolower($columnName);
        return isset($this->_columns[$columnName]);
    }

    /**
     * Get a column instance
     * 
     * @param  string $columnName
     * @return Column
     */
    public function getColumn($columnName)
    {
        $columnName = strtolower($columnName);
        if (!$this->hasColumn($columnName)) {
            throw SchemaException::columnDoesNotExist($columnName);
        }

        return $this->_columns[$columnName];
    }

    /**
     * @return Index
     */
    public function getPrimaryKey()
    {
        return $this->getIndex($this->_primaryKeyName);
    }

    /**
     * @param  string $indexName
     * @return bool
     */
    public function hasIndex($indexName)
    {
        $indexName = strtolower($indexName);
        return (isset($this->_indexes[$indexName]));
    }

    /**
     * @param  string $indexName
     * @return Index
     */
    public function getIndex($indexName)
    {
        $indexName = strtolower($indexName);
        if (!$this->hasIndex($indexName)) {
            throw SchemaException::indexDoesNotExist($indexName);
        }
        return $this->_indexes[$indexName];
    }

    /**
     * @return array
     */
    public function getIndexes()
    {
        return $this->_indexes;
    }

    /**
     * Get Constraints
     *
     * @return array
     */
    public function getForeignKeys()
    {
        return $this->_fkConstraints;
    }

    public function hasOption($name)
    {
        return isset($this->_options[$name]);
    }

    public function getOption($name)
    {
        return $this->_options[$name];
    }

    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @param Visitor $visitor
     */
    public function visit(Visitor $visitor)
    {
        $visitor->acceptTable($this);

        foreach ($this->getColumns() AS $column) {
            $visitor->acceptColumn($this, $column);
        }

        foreach ($this->getIndexes() AS $index) {
            $visitor->acceptIndex($this, $index);
        }

        foreach ($this->getForeignKeys() AS $constraint) {
            $visitor->acceptForeignKey($this, $constraint);
        }
    }

    /**
     * Clone of a Table triggers a deep clone of all affected assets
     */
    public function __clone()
    {
        foreach ($this->_columns AS $k => $column) {
            $this->_columns[$k] = clone $column;
        }
        foreach ($this->_indexes AS $k => $index) {
            $this->_indexes[$k] = clone $index;
        }
        foreach ($this->_fkConstraints AS $k => $fk) {
            $this->_fkConstraints[$k] = clone $fk;
            $this->_fkConstraints[$k]->setLocalTable($this);
        }
    }
}