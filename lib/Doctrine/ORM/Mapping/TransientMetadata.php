<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Reflection\ReflectionService;
use ReflectionProperty;

class TransientMetadata implements Property
{
    /** @var ClassMetadata */
    protected $declaringClass;

    /** @var ReflectionProperty */
    protected $reflection;

    /** @var string */
    protected $name;

    public function __construct(string $name)
    {
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
    public function isPrimaryKey() : bool
    {
        return false;
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
