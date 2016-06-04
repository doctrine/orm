<?php

namespace Doctrine\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\DBAL\Types\Type;

class FieldMetadata extends ColumnMetadata implements Property
{
    /**
     * @var ClassMetadata
     */
    private $declaringClass;

    /**
     * @var ClassMetadata
     */
    private $currentClass;

    /**
     * @var \ReflectionProperty
     */
    private $reflection;

    /**
     * @var string
     */
    private $name;

    /**
     * FieldMetadata constructor.
     *
     * @param ClassMetadata $declaringClass
     * @param string        $fieldName
     * @param Type          $type
     */
    public function __construct(ClassMetadata $declaringClass, $fieldName, Type $type)
    {
        parent::__construct(null, $fieldName, $type);

        $this->declaringClass = $declaringClass;
        $this->currentClass   = $declaringClass;
        $this->name           = $fieldName;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentClass()
    {
        return $this->currentClass;
    }

    /**
     * @param ClassMetadata $currentClass
     */
    public function setCurrentClass(ClassMetadata $currentClass)
    {
        $this->currentClass = $currentClass;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @param string $columnName
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
    }

    /**
     * {@inheritdoc}
     */
    public function isInherited()
    {
        return $this->declaringClass !== $this->currentClass;
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
        $this->reflection = $reflectionService->getAccessibleProperty(
            $this->getDeclaringClass()->name,
            $this->fieldName
        );
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return [
            'declaringClass'   => $this->declaringClass->name,
            'currentClass'     => $this->currentClass->name,
            'tableName'        => $this->tableName,
            'columnName'       => $this->columnName,
            'columnDefinition' => $this->columnDefinition,
            'length'           => $this->length,
            'scale'            => $this->scale,
            'precision'        => $this->precision,
            'options'          => $this->options,
            'id'               => $this->primaryKey,
            'nullable'         => $this->nullable,
            'unique'           => $this->unique,
        ];
    }
}