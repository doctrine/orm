<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Reflection\ReflectionService;
use ReflectionProperty;

/**
 * @property MappedSuperClassMetadata $parent
 */
class EmbeddedClassMetadata extends ComponentMetadata implements Property
{
    /** @var ClassMetadata */
    private $declaringClass;

    /** @var ReflectionProperty */
    private $reflection;

    /** @var string */
    private $name;

    /** @var bool */
    protected $primaryKey = false;

    public function __construct(string $name, string $className, ?MappedSuperClassMetadata $parent = null)
    {
        parent::__construct($className, $parent);

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

    /**
     * {@inheritdoc}
     */
    public function setName($name) : void
    {
        $this->name = $name;
    }

    public function setPrimaryKey(bool $isPrimaryKey) : void
    {
        $this->primaryKey = $isPrimaryKey;
    }

    public function isPrimaryKey() : bool
    {
        return $this->primaryKey;
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
}
