<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Reflection\ReflectionService;

interface Property
{
    /**
     * @param ClassMetadata $declaringClass
     */
    public function setDeclaringClass(ClassMetadata $declaringClass);

    /**
     * @return ClassMetadata
     */
    public function getDeclaringClass();

    /**
     * @param object $object
     * @param mixed  $value
     */
    public function setValue($object, $value);

    /**
     * @param object $object
     *
     * @return mixed
     */
    public function getValue($object);

    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function isPrimaryKey();

    /**
     * @param \ReflectionProperty $reflectionProperty
     */
    public function setReflectionProperty(\ReflectionProperty $reflectionProperty);

    /**
     * @param ReflectionService $reflectionService
     */
    public function wakeupReflection(ReflectionService $reflectionService);
}
