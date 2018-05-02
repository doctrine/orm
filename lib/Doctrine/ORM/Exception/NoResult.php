<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

/**
 * Exception thrown when an ORM query unexpectedly does not return any results.
 */
final class NoResult extends UnexpectedResult
{
    public function __construct()
    {
        parent::__construct('No result was found for query although at least one row was expected.');
    }
}
