<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Types\Type;

abstract class ColumnMetadata
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $columnName;

    /**
     * @var Type
     */
    protected $type;

    /**
     * @var string
     */
    protected $columnDefinition;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var boolean
     */
    protected $primaryKey = false;

    /**
     * @var boolean
     */
    protected $nullable = false;

    /**
     * @var boolean
     */
    protected $unique = false;

    /**
     * ColumnMetadata constructor.
     *
     * @param string $columnName
     * @param Type   $type
     *
     * @todo Leverage this implementation instead of default, blank constructor
     */
    /*public function __construct(string $columnName, Type $type)
    {
        $this->columnName = $columnName;
        $this->type       = $type;
    }*/

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @todo Enable scalar typehint here
     *
     * @param string $tableName
     */
    public function setTableName(/*string*/ $tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @param string $columnName
     */
    public function setColumnName(string $columnName)
    {
        $this->columnName = $columnName;
    }

    /**
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Type $type
     */
    public function setType(Type $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        return $this->type->getName();
    }

    /**
     * @return string
     */
    public function getColumnDefinition()
    {
        return $this->columnDefinition;
    }

    /**
     * @param string $columnDefinition
     */
    public function setColumnDefinition(string $columnDefinition)
    {
        $this->columnDefinition = $columnDefinition;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param bool $isPrimaryKey
     */
    public function setPrimaryKey(bool $isPrimaryKey)
    {
        $this->primaryKey = $isPrimaryKey;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @param bool $isNullable
     */
    public function setNullable(bool $isNullable)
    {
        $this->nullable = $isNullable;
    }

    /**
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @param bool $isUnique
     */
    public function setUnique(bool $isUnique)
    {
        $this->unique = $isUnique;
    }

    /**
     * @return bool
     */
    public function isUnique()
    {
        return $this->unique;
    }
}
