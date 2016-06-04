<?php

namespace Doctrine\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\DBAL\Types\Type;

interface Property
{
    /**
     * @return ClassMetadata
     */
    public function getDeclaringClass();

    /**
     * @return ClassMetadata
     */
    public function getCurrentClass();

    /**
     * @return ClassMetadata
     */
    public function setCurrentClass(ClassMetadata $currentClass);

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
     * @return Type
     */
    public function getType();

    /**
     * @return string
     */
    public function getTypeName();

    /**
     * @param ReflectionService $reflectionService
     */
    public function wakeupReflection(ReflectionService $reflectionService);
}