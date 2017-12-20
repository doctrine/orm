<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Reflection\ReflectionService;

/**
 * Class AssociationMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class AssociationMetadata implements Property
{
    /** @var ClassMetadata */
    private $declaringClass;

    /** @var \ReflectionProperty */
    private $reflection;

    /** @var string */
    private $name;

    /** @var boolean */
    protected $primaryKey = false;

    /** @var string */
    private $fetchMode = FetchMode::LAZY;

    /** @var string */
    private $targetEntity;

    /** @var string */
    private $sourceEntity;

    /** @var null|string */
    private $mappedBy;

    /** @var null|string */
    private $inversedBy;

    /** @var array<string> */
    private $cascade = [];

    /** @var bool */
    private $owningSide = true;

    /** @var bool */
    private $orphanRemoval = false;

    /** @var null|CacheMetadata */
    private $cache;

    /**
     * AssociationMetadata constructor.
     *
     * @param string $name
     */
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

    /**
     * @param ComponentMetadata $declaringClass
     */
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
     * @return string
     */
    public function getTargetEntity() : string
    {
        return $this->targetEntity;
    }

    /**
     * @param string $targetEntity
     */
    public function setTargetEntity(string $targetEntity) : void
    {
        $this->targetEntity = $targetEntity;
    }

    /**
     * @return string
     */
    public function getSourceEntity() : string
    {
        return $this->sourceEntity;
    }

    /**
     * @param string $sourceEntity
     */
    public function setSourceEntity(string $sourceEntity) : void
    {
        $this->sourceEntity = $sourceEntity;
    }

    /**
     * @return array
     */
    public function getCascade() : array
    {
        return $this->cascade;
    }

    /**
     * @param array $cascade
     */
    public function setCascade(array $cascade) : void
    {
        $this->cascade = $cascade;
    }

    /**
     * @param bool $owningSide
     */
    public function setOwningSide(bool $owningSide) : void
    {
        $this->owningSide = $owningSide;
    }

    /**
     * @return bool
     */
    public function isOwningSide() : bool
    {
        return $this->owningSide;
    }

    /**
     * @return string
     */
    public function getFetchMode() : string
    {
        return $this->fetchMode;
    }

    /**
     * @param string $fetchMode
     */
    public function setFetchMode(string $fetchMode) : void
    {
        $this->fetchMode = $fetchMode;
    }

    /**
     * @return string|null
     */
    public function getMappedBy() : ?string
    {
        return $this->mappedBy;
    }

    /**
     * @param string $mappedBy
     */
    public function setMappedBy(string $mappedBy) : void
    {
        $this->mappedBy = $mappedBy;
    }

    /**
     * @return null|string
     */
    public function getInversedBy() : ?string
    {
        return $this->inversedBy;
    }

    /**
     * @param null|string $inversedBy
     */
    public function setInversedBy(string $inversedBy = null) : void
    {
        $this->inversedBy = $inversedBy;
    }

    /**
     * @param bool $orphanRemoval
     */
    public function setOrphanRemoval(bool $orphanRemoval) : void
    {
        $this->orphanRemoval = $orphanRemoval;
    }

    /**
     * @return bool
     */
    public function isOrphanRemoval() : bool
    {
        return $this->orphanRemoval;
    }

    /**
     * @return null|CacheMetadata
     */
    public function getCache() : ?CacheMetadata
    {
        return $this->cache;
    }

    /**
     * @param null|CacheMetadata $cache
     */
    public function setCache(CacheMetadata $cache = null) : void
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
    public function setReflectionProperty(\ReflectionProperty $reflectionProperty) : void
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
