<?php

declare(strict_types=1);

namespace Doctrine\ORM\Reflection;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

/**
 * Very simple reflection service abstraction.
 *
 * This is required inside metadata layers that may require either
 * static or runtime reflection.
 */
interface ReflectionService
{
    /**
     * Returns an array of the parent classes (not interfaces) for the given class.
     *
     * @return string[]
     *
     * @throws InvalidArgumentException If provided argument is not a valid class name.
     */
    public function getParentClasses(string $className) : array;

    /**
     * Returns the shortname of a class.
     */
    public function getClassShortName(string $className) : string;

    public function getClassNamespace(string $className) : string;

    /**
     * Returns a reflection class instance or null.
     */
    public function getClass(string $className) : ?ReflectionClass;

    /**
     * Returns an accessible property (setAccessible(true)) or null.
     */
    public function getAccessibleProperty(string $className, string $propertyName) : ?ReflectionProperty;

    /**
     * Checks if the class have a public method with the given name.
     */
    public function hasPublicMethod(string $className, string $methodName) : bool;
}
