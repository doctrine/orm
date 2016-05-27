<?php

namespace Doctrine\ORM;

/**
 * Every ValueObject which implements this interface can be safely
 * set on an Entity property mapped for ORM.
 *
 * @package Doctrine\ORM
 */
interface DoctrineValueObject
{
    /**
     * ValueObject should be usable as a string.
     *
     * @return string
     */
    public function __toString();

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function equals($value);
}
