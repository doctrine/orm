<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Reflection\ReflectionService;

/**
 * Class EmbeddedClassMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 *
 * @property MappedSuperClassMetadata $parent
 */
class EmbeddedClassMetadata extends ComponentMetadata implements Property
{
    /** @var ClassMetadata */
    private $declaringClass;

    /** @var \ReflectionProperty */
    private $reflection;

    /** @var string */
    private $name;

    /** @var boolean */
    protected $primaryKey = false;

    /**
     * EmbeddedClassMetadata constructor.
     *
     * @param string                        $name
     * @param string                        $className
     * @param MappedSuperClassMetadata|null $parent
     */
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
}
