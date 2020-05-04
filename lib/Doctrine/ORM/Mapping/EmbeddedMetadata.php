<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Reflection\ReflectionService;
use Doctrine\ORM\Sequencing\Executor\ValueGenerationExecutor;
use ReflectionProperty;

class EmbeddedMetadata implements Property
{
    /** @var ClassMetadata */
    private $declaringClass;

    /** @var ReflectionProperty */
    private $reflection;

    /** @var string */
    private $name;

    /** @var bool */
    protected $primaryKey = false;

    /** @var string */
    private $targetEntity;

    /** @var string */
    private $sourceEntity;

    /** @var string|null */
    private $columnPrefix;

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

    public function getTargetEntity() : string
    {
        return $this->targetEntity;
    }

    public function setTargetEntity(string $targetEntity) : void
    {
        $this->targetEntity = $targetEntity;
    }

    public function getSourceEntity() : string
    {
        return $this->sourceEntity;
    }

    public function setSourceEntity(string $sourceEntity) : void
    {
        $this->sourceEntity = $sourceEntity;
    }

    public function getColumnPrefix() : ?string
    {
        return $this->columnPrefix;
    }

    public function setColumnPrefix(string $columnPrefix) : void
    {
        $this->columnPrefix = $columnPrefix;
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

    public function getValueGenerationExecutor(AbstractPlatform $platform) : ?ValueGenerationExecutor
    {
        return $this->isPrimaryKey()
            ? new EmbeddedValueGeneratorExecutor()
            : null;
    }
}
