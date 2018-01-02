<?php


declare(strict_types=1);

namespace Doctrine\ORM\Reflection;

/**
 * Very simple reflection service abstraction.
 *
 * This is required inside metadata layers that may require either
 * static or runtime reflection.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
interface ReflectionService
{
    /**
     * Returns an array of the parent classes (not interfaces) for the given class.
     *
     * @param string $className
     *
     * @throws \InvalidArgumentException If provided argument is not a valid class name.
     *
     * @return array
     */
    public function getParentClasses(string $className) : array;

    /**
     * Returns the shortname of a class.
     *
     * @param string $className
     *
     * @return string
     */
    public function getClassShortName(string $className) : string;

    /**
     * @param string $className
     *
     * @return string
     */
    public function getClassNamespace(string $className) : string;

    /**
     * Returns a reflection class instance or null.
     *
     * @param string $className
     *
     * @return \ReflectionClass|null
     */
    public function getClass(string $className) : ?\ReflectionClass;

    /**
     * Returns an accessible property (setAccessible(true)) or null.
     *
     * @param string $className
     * @param string $propertyName
     *
     * @return \ReflectionProperty|null
     */
    public function getAccessibleProperty(string $className, string $propertyName) : ?\ReflectionProperty;

    /**
     * Checks if the class have a public method with the given name.
     *
     * @param mixed $className
     * @param mixed $methodName
     *
     * @return bool
     */
    public function hasPublicMethod(string $className, string $methodName) : bool;
}
