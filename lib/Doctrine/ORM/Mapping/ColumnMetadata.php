<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Types\Type;

/**
 * Class ColumnMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
abstract class ColumnMetadata
{
    /**
     * @var string|null
     */
    protected $tableName;

    /**
     * @var string|null
     */
    protected $columnName;

    /**
     * @var Type|null
     */
    protected $type;

    /**
     * @var string|null
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
     * @return string|null
     */
    public function getTableName() : ?string
    {
        return $this->tableName;
    }

    /**
     * @todo Enable scalar typehint here
     *
     * @param string $tableName
     */
    public function setTableName(/*string*/ $tableName) : void
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string|null
     */
    public function getColumnName() : ?string
    {
        return $this->columnName;
    }

    /**
     * @param string $columnName
     */
    public function setColumnName(string $columnName) : void
    {
        $this->columnName = $columnName;
    }

    /**
     * @return Type|null
     */
    public function getType() : ?Type
    {
        return $this->type;
    }

    /**
     * @param Type $type
     */
    public function setType(Type $type) : void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getTypeName() : string
    {
        return $this->type->getName();
    }

    /**
     * @return string|null
     */
    public function getColumnDefinition() : ?string
    {
        return $this->columnDefinition;
    }

    /**
     * @param string $columnDefinition
     */
    public function setColumnDefinition(string $columnDefinition) : void
    {
        $this->columnDefinition = $columnDefinition;
    }

    /**
     * @return array
     */
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options) : void
    {
        $this->options = $options;
    }

    /**
     * @param bool $isPrimaryKey
     */
    public function setPrimaryKey(bool $isPrimaryKey) : void
    {
        $this->primaryKey = $isPrimaryKey;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey() : bool
    {
        return $this->primaryKey;
    }

    /**
     * @param bool $isNullable
     */
    public function setNullable(bool $isNullable) : void
    {
        $this->nullable = $isNullable;
    }

    /**
     * @return bool
     */
    public function isNullable() : bool
    {
        return $this->nullable;
    }

    /**
     * @param bool $isUnique
     */
    public function setUnique(bool $isUnique) : void
    {
        $this->unique = $isUnique;
    }

    /**
     * @return bool
     */
    public function isUnique() : bool
    {
        return $this->unique;
    }
}
