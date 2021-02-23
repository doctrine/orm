<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Types\Type;

abstract class ColumnMetadata
{
    /** @var string|null */
    protected $tableName;

    /** @var string|null */
    protected $columnName;

    /** @var Type|null */
    protected $type;

    /** @var string|null */
    protected $columnDefinition;

    /** @var mixed[] */
    protected $options = [];

    /** @var bool */
    protected $primaryKey = false;

    /** @var bool */
    protected $nullable = false;

    /** @var bool */
    protected $unique = false;

    /**
     * @todo Leverage this implementation instead of default, blank constructor
     */
    /*public function __construct(string $columnName, Type $type)
    {
        $this->columnName = $columnName;
        $this->type       = $type;
    }*/

    /**
     * Table name
     */
    public function getTableName() : ?string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     *
     * @todo Enable scalar typehint here
     */
    public function setTableName($tableName) : void
    {
        $this->tableName = $tableName;
    }

    public function getColumnName() : ?string
    {
        return $this->columnName;
    }

    public function setColumnName(string $columnName) : void
    {
        $this->columnName = $columnName;
    }

    public function getType() : ?Type
    {
        return $this->type;
    }

    public function setType(Type $type) : void
    {
        $this->type = $type;
    }

    public function getTypeName() : string
    {
        return $this->type->getName();
    }

    public function getColumnDefinition() : ?string
    {
        return $this->columnDefinition;
    }

    public function setColumnDefinition(string $columnDefinition) : void
    {
        $this->columnDefinition = $columnDefinition;
    }

    /**
     * @return mixed[]
     */
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * @param mixed[] $options
     */
    public function setOptions(array $options) : void
    {
        $this->options = $options;
    }

    public function setPrimaryKey(bool $isPrimaryKey) : void
    {
        $this->primaryKey = $isPrimaryKey;
    }

    public function isPrimaryKey() : bool
    {
        return $this->primaryKey;
    }

    public function setNullable(bool $isNullable) : void
    {
        $this->nullable = $isNullable;
    }

    public function isNullable() : bool
    {
        return $this->nullable;
    }

    public function setUnique(bool $isUnique) : void
    {
        $this->unique = $isUnique;
    }

    public function isUnique() : bool
    {
        return $this->unique;
    }
}
