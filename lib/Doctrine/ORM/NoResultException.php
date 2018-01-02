<?php

declare(strict_types=1);

namespace Doctrine\ORM;

/**
 * Exception thrown when an ORM query unexpectedly does not return any results.
 *
 * @author robo
 * @since 2.0
 */
class NoResultException extends UnexpectedResultException
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct('No result was found for query although at least one row was expected.');
    }
}
