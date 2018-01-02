<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Reflection\ReflectionService;

interface Property
{
    /**
     * @param ComponentMetadata $declaringClass
     */
    public function setDeclaringClass(ComponentMetadata $declaringClass) : void;

    /**
     * @return ComponentMetadata
     */
    public function getDeclaringClass() : ComponentMetadata;

    /**
     * @param object $object
     * @param mixed  $value
     */
    public function setValue($object, $value) : void;

    /**
     * @param object $object
     *
     * @return mixed
     */
    public function getValue($object);

    /**
     * @return string
     */
    public function getName() : string;

    /**
     * @return bool
     */
    public function isPrimaryKey() : bool;

    /**
     * @param \ReflectionProperty $reflectionProperty
     */
    public function setReflectionProperty(\ReflectionProperty $reflectionProperty) : void;

    /**
     * @param ReflectionService $reflectionService
     */
    public function wakeupReflection(ReflectionService $reflectionService) : void;
}
