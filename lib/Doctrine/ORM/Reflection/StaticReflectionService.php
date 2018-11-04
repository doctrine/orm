<?php

declare(strict_types=1);

namespace Doctrine\ORM\Reflection;

use ReflectionClass;
use ReflectionProperty;
use function strpos;
use function strrev;
use function strrpos;
use function substr;

/**
 * PHP Runtime Reflection Service.
 */
class StaticReflectionService implements ReflectionService
{
    /**
     * {@inheritDoc}
     */
    public function getParentClasses(string $className) : array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getClassShortName(string $className) : string
    {
        if (strpos($className, '\\') !== false) {
            $className = substr($className, strrpos($className, '\\')+1);
        }
        return $className;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassNamespace(string $className) : string
    {
        $namespace = '';

        if (strpos($className, '\\') !== false) {
            $namespace = strrev(substr(strrev($className), strpos(strrev($className), '\\')+1));
        }

        return $namespace;
    }

    /**
     * {@inheritDoc}
     */
    public function getClass(string $className) : ?ReflectionClass
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessibleProperty(string $className, string $propertyName) : ?ReflectionProperty
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function hasPublicMethod(string $className, string $methodName) : bool
    {
        return true;
    }
}
