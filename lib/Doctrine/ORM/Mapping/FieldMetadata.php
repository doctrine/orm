<?php

namespace Doctrine\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class FieldMetadata implements PropertyMetadata
{
    /**
     * @var ClassMetadata
     */
    private $declaringClass;

    /**
     * @var \ReflectionProperty
     */
    private $reflection;

    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var Type
     */
    private $type;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $columnName;

    /**
     * @var string
     */
    private $columnDefinition;

    /**
     * @var integer
     */
    private $length;

    /**
     * @var integer
     */
    private $scale;

    /**
     * @var integer
     */
    private $precision;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var boolean
     */
    private $isPrimaryKey = false;

    /**
     * @var boolean
     */
    private $isNullable = false;

    /**
     * @var boolean
     */
    private $isUnique = false;

    /**
     * FieldMetadata constructor.
     *
     * @param ClassMetadata $currentClass
     * @param string        $fieldName
     * @param Type          $type
     */
    public function __construct(ClassMetadata $currentClass, $fieldName, Type $type)
    {
        $this->declaringClass = $currentClass;
        $this->fieldName      = $fieldName;
        $this->type           = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentClass()
    {
        return $this->declaringClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($object, $value)
    {
        $this->reflection->setValue($object, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($object)
    {
        return $this->reflection->getValue($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldName()
    {
        return $this->fieldName;
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
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
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
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
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
        $this->isPrimaryKey = $isPrimaryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function isPrimaryKey()
    {
        return $this->isPrimaryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setNullable($isNullable)
    {
        $this->isNullable = $isNullable;
    }

    /**
     * {@inheritdoc}
     */
    public function isNullable()
    {
        return $this->isNullable;
    }

    /**
     * {@inheritdoc}
     */
    public function setUnique($isUnique)
    {
        $this->isUnique = $isUnique;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnique()
    {
        return $this->isUnique;
    }

    /**
     * {@inheritdoc}
     */
    public function isInherited()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isAssociation()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isField()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function wakeupReflection(ReflectionService $reflectionService)
    {
        $this->reflection = $reflectionService->getAccessibleProperty($this->getDeclaringClass()->name, $this->fieldName);
    }

    /**
     * @param AbstractPlatform   $platform
     * @param QuoteStrategy|null $quoteStrategy
     *
     * @return string
     */
    public function getQuotedColumnName(AbstractPlatform $platform, QuoteStrategy $quoteStrategy = null)
    {
        return $quoteStrategy
            ? $quoteStrategy->getColumnName($this->fieldName, $this->declaringClass, $platform)
            : $platform->quoteIdentifier($this->columnName)
        ;
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return [
            'tableName'        => $this->tableName,
            'columnName'       => $this->columnName,
            'columnDefinition' => $this->columnDefinition,
            'length'           => $this->length,
            'scale'            => $this->scale,
            'precision'        => $this->precision,
            'options'          => $this->options,
            'id'               => $this->isPrimaryKey,
            'nullable'         => $this->isNullable,
            'unique'           => $this->isUnique,
        ];
    }
}