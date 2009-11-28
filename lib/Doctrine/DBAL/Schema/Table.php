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
    protected $_constraints = array();

    /**
     * @var array
     */
    protected $_options = array();

    /**
     * @var bool
     */
    protected $_idGeneratorType = self::ID_NONE;

    /**
     *
     * @param string $tableName
     * @param array $columns
     * @param array $indexes
     * @param array $constraints
     * @param int $idGeneratorType
     * @param array $options
     */
    public function __construct($tableName, array $columns=array(), array $indexes=array(), array $constraints=array(), $idGeneratorType=self::ID_NONE, array $options=array())
    {
        $this->_name = $tableName;
        $this->_idGeneratorType = $idGeneratorType;
        
        foreach ($columns AS $column) {
            $this->_addColumn($column);
        }
        
        foreach ($indexes AS $idx) {
            $this->_addIndex($idx);
        }

        foreach ($constraints AS $constraint) {
            $this->_addConstraint($constraint);
        }

        $this->_options = $options;
    }

    /**
     * Set Primary Key
     *
     * @param array $columns
     * @param string $indexName
     * @return Table
     */
    public function setPrimaryKey(array $columns, $indexName=false)
    {
        return $this->_createIndex($columns, $indexName?:"primary", true, true);
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
    public function addIndex(array $columnNames, $indexName=null)
    {
        return $this->_createIndex($columnNames, $indexName, false, false);
    }

    /**
     *
     * @param array $columnNames
     * @param string $indexName
     * @return Table
     */
    public function addUniqueIndex(array $columnNames, $indexName=null)
    {
        return $this->_createIndex($columnNames, $indexName, true, false);
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

        foreach ($columnNames AS $columnName) {
            if (!isset($this->_columns[$columnName])) {
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
     * @return Table
     */
    public function createColumn($columnName, $typeName, array $options=array())
    {
        $column = new Column($columnName, Type::getType($typeName), $options);

        $this->_addColumn($column);
        return $this;
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
        $column = $this->getColumn($columnName);
        unset($this->_columns[$columnName]);
        return $this;
    }


    /**
     * Add a foreign key constraint
     *
     * @param Table $foreignTable
     * @param array $localColumns
     * @param array $foreignColumns
     * @param array $options
     * @return Table
     */
    public function addForeignKeyConstraint($foreignTable, array $localColumnNames, array $foreignColumnNames, $name=null, array $options=array())
    {
        if ($foreignTable instanceof Table) {
            $foreignTableName = $foreignTable->getName();

            foreach ($foreignColumnNames AS $columnName) {
                if (!$foreignTable->hasColumn($columnName)) {
                    throw SchemaException::columnDoesNotExist($columnName);
                }
            }
        } else {
            $foreignTableName = $foreignTable;
        }

        foreach ($localColumnNames AS $columnName) {
            if (!$this->hasColumn($columnName)) {
                throw SchemaException::columnDoesNotExist($columnName);
            }
        }

        $constraint = new ForeignKeyConstraint(
            $localColumnNames, $foreignTableName, $foreignColumnNames, $name, $options
        );
        $this->_addConstraint($constraint);
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
        if (isset($this->_columns[$columnName])) {
            throw SchemaException::columnAlreadyExists($this->_name, $columnName);
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
        $indexName = $index->getName();

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
     * @param Constraint $constraint
     */
    protected function _addConstraint(Constraint $constraint)
    {
        $this->_constraints[] = $constraint;
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
        return $this->_columns;
    }


    /**
     * Does this table have a column with the given name?
     *
     * @param  string $columnName
     * @return bool
     */
    public function hasColumn($columnName)
    {
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
        return (isset($this->_indexes[$indexName]));
    }

    /**
     * @param  string $indexName
     * @return Index
     */
    public function getIndex($indexName)
    {
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
    public function getConstraints()
    {
        return $this->_constraints;
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
            $visitor->acceptColunn($this, $column);
        }

        foreach ($this->getIndexes() AS $index) {
            $visitor->acceptIndex($this, $index);
        }

        foreach ($this->getConstraints() AS $constraint) {
            if ($constraint instanceof ForeignKeyConstraint) {
                $visitor->acceptForeignKey($this, $constraint);
            } else {
                $visitor->acceptCheckConstraint($this, $constraint);
            }
        }
    }
}