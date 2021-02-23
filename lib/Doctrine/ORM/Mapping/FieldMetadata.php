<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Reflection\ReflectionService;
use ReflectionProperty;

class FieldMetadata extends LocalColumnMetadata implements Property
{
    /** @var ComponentMetadata */
    protected $declaringClass;

    /** @var ReflectionProperty */
    protected $reflection;

    /** @var string */
    protected $name;

    /** @var bool */
    protected $versioned = false;

    /** @var string|null */
    protected $className;

    public function __construct(string $name/*, string $columnName, Type $type*/)
    {
//        @todo Leverage this implementation instead of default, simple constructor
//        parent::__construct($columnName, $type);
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeclaringClass() : ComponentMetadata
    {
        return $this->declaringClass;
    }

    public function setDeclaringClass(ComponentMetadata $declaringClass) : void
    {
        $this->declaringClass = $declaringClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return $this->name;
    }

    public function isVersioned() : bool
    {
        return $this->versioned;
    }

    public function setVersioned(bool $versioned) : void
    {
        $this->versioned = $versioned;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($object, $value) : void
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
    public function isAssociation() : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isField() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setReflectionProperty(ReflectionProperty $reflectionProperty) : void
    {
        $this->reflection = $reflectionProperty;
    }

    /**
     * {@inheritdoc}
     */
    public function wakeupReflection(ReflectionService $reflectionService) : void
    {
        $this->setReflectionProperty(
            $reflectionService->getAccessibleProperty($this->declaringClass->getClassName(), $this->name)
        );
    }

    /**
     * __sleep
     *
     * Serialization of ReflectionProperty generates an exception on php >= 7.4
     * @see https://raw.githubusercontent.com/php/php-src/PHP7.4/UPGRADING for explanation on that
     */
    public function __sleep()
    {
        $this->className = $this->reflection->class;
        return [
            'declaringClass',
            'name',
            'versioned',
            'length',
            'scale',
            'precision',
            'valueGenerator',
            'tableName',
            'columnName',
            'options',
            'primaryKey',
            'nullable',
            'unique',
            'className'
        ];
    }

    public function __wakeup()
    {
        $this->reflection = new ReflectionProperty($this->className, $this->name);
    }
}
