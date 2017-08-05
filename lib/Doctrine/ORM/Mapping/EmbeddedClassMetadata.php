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
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }

    /**
     * @param ClassMetadata $declaringClass
     */
    public function setDeclaringClass(ClassMetadata $declaringClass)
    {
        $this->declaringClass = $declaringClass;
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
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param bool $isPrimaryKey
     */
    public function setPrimaryKey(bool $isPrimaryKey)
    {
        $this->primaryKey = $isPrimaryKey;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey()
    {
        return $this->primaryKey;
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
    public function setReflectionProperty(\ReflectionProperty $reflectionProperty)
    {
        $this->reflection = $reflectionProperty;
    }

    /**
     * {@inheritdoc}
     */
    public function wakeupReflection(ReflectionService $reflectionService)
    {
        $this->setReflectionProperty(
            $reflectionService->getAccessibleProperty($this->declaringClass->getClassName(), $this->name)
        );
    }
}
