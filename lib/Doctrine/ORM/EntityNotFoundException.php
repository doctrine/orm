<?php

namespace Doctrine\ORM;

/**
 * Exception thrown when a Proxy fails to retrieve an Entity result.
 *
 * @author robo
 * @since 2.0
 */
class EntityNotFoundException extends ORMException
{
    public function __construct()
    {
        parent::__construct('Entity was found although one item was expected.');
    }
}