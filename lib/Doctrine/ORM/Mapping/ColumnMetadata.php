<?php

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class ColumnMetadata
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
     * @var integer
     */
    protected $length = 255;

    /**
     * @var integer
     */
    protected $scale = 0;

    /**
     * @var integer
     */
    protected $precision = 0;

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
     * @param string $tableName
     * @param string $fieldName
     * @param Type   $type
     */
    public function __construct($tableName, $fieldName, Type $type)
    {
        $this->tableName = $tableName;
        $this->fieldName = $fieldName;
        $this->type      = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeName()
    {
        return $this->type->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnDefinition()
    {
        return $this->columnDefinition;
    }

    /**
     * {@inheritdoc}
     */
    public function setColumnDefinition($columnDefinition)
    {
        $this->columnDefinition = $columnDefinition;
    }

    /**
     * @return integer
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param integer $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return integer
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param integer $scale
     */
    public function setScale($scale)
    {
        $this->scale = $scale;
    }

    /**
     * @return integer
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param integer $precision
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
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
     * {@inheritdoc}
     */
    public function setPrimaryKey($isPrimaryKey)
    {
        $this->primaryKey = $isPrimaryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function isPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setNullable($isNullable)
    {
        $this->nullable = $isNullable;
    }

    /**
     * {@inheritdoc}
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * {@inheritdoc}
     */
    public function setUnique($isUnique)
    {
        $this->unique = $isUnique;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnique()
    {
        return $this->unique;
    }
}