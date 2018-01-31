<?php

declare(strict_types=1);

namespace Doctrine\ORM;

/**
 * Exception thrown when an ORM query unexpectedly does not return any results.
 */
class NoResultException extends UnexpectedResultException
{
    public const DEFAULT_MESSAGE = 'No result was found for query although at least one row was expected.';
    
    /**
     * Constructor.
     */
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? self::DEFAULT_MESSAGE);
    }
}
