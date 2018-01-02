<?php

declare(strict_types=1);

namespace Doctrine\ORM;

/**
 * Exception thrown when an ORM query unexpectedly returns more than one result.
 *
 * @author robo
 * @since 2.0
 */
class NonUniqueResultException extends UnexpectedResultException
{
    const DEFAULT_MESSAGE = 'More than one result was found for query although one row or none was expected.';

    public function __construct(string $message = null)
    {
        parent::__construct($message ?? self::DEFAULT_MESSAGE);
    }
}
