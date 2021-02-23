<?php

declare(strict_types=1);

namespace Doctrine\ORM\Reflection;

use Doctrine\Common\Persistence\Mapping\MappingException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use function class_exists;
use function class_parents;

/**
 * PHP Runtime Reflection Service.
 */
class RuntimeReflectionService implements ReflectionService
{
    /**
     * {@inheritdoc}
     */
    public function getParentClasses(string $className) : array
    {
        if (! class_exists($className)) {
            throw MappingException::nonExistingClass($className);
        }

        return class_parents($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassShortName(string $className) : string
    {
        $reflectionClass = new ReflectionClass($className);

        return $reflectionClass->getShortName();
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNamespace(string $className) : string
    {
        $reflectionClass = new ReflectionClass($className);

        return $reflectionClass->getNamespaceName();
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(string $className) : ?ReflectionClass
    {
        return new ReflectionClass($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessibleProperty(string $className, string $propertyName) : ?ReflectionProperty
    {
        $reflectionProperty = new ReflectionProperty($className, $propertyName);

        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPublicMethod(string $className, string $methodName) : bool
    {
        try {
            $reflectionMethod = new ReflectionMethod($className, $methodName);
        } catch (ReflectionException $e) {
            return false;
        }

        return $reflectionMethod->isPublic();
    }
}
