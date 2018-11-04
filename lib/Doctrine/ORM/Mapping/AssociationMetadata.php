<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Reflection\ReflectionService;
use ReflectionProperty;

/**
 * Class AssociationMetadata
 */
class AssociationMetadata implements Property
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
    private $fetchMode = FetchMode::LAZY;

    /** @var string */
    private $targetEntity;

    /** @var string */
    private $sourceEntity;

    /** @var string|null */
    private $mappedBy;

    /** @var string|null */
    private $inversedBy;

    /** @var string[] */
    private $cascade = [];

    /** @var bool */
    private $owningSide = true;

    /** @var bool */
    private $orphanRemoval = false;

    /** @var CacheMetadata|null */
    private $cache;

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

    /**
     * @return string[]
     */
    public function getCascade() : array
    {
        return $this->cascade;
    }

    /**
     * @param string[] $cascade
     */
    public function setCascade(array $cascade) : void
    {
        $this->cascade = $cascade;
    }

    public function setOwningSide(bool $owningSide) : void
    {
        $this->owningSide = $owningSide;
    }

    public function isOwningSide() : bool
    {
        return $this->owningSide;
    }

    public function getFetchMode() : string
    {
        return $this->fetchMode;
    }

    public function setFetchMode(string $fetchMode) : void
    {
        $this->fetchMode = $fetchMode;
    }

    public function getMappedBy() : ?string
    {
        return $this->mappedBy;
    }

    public function setMappedBy(string $mappedBy) : void
    {
        $this->mappedBy = $mappedBy;
    }

    public function getInversedBy() : ?string
    {
        return $this->inversedBy;
    }

    public function setInversedBy(?string $inversedBy = null) : void
    {
        $this->inversedBy = $inversedBy;
    }

    public function setOrphanRemoval(bool $orphanRemoval) : void
    {
        $this->orphanRemoval = $orphanRemoval;
    }

    public function isOrphanRemoval() : bool
    {
        return $this->orphanRemoval;
    }

    public function getCache() : ?CacheMetadata
    {
        return $this->cache;
    }

    public function setCache(?CacheMetadata $cache = null) : void
    {
        $this->cache = $cache;
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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isField() : bool
    {
        return false;
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

    public function __clone()
    {
        if ($this->cache) {
            $this->cache = clone $this->cache;
        }
    }
}
