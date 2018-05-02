<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

/**
 * Is thrown when a transaction is required for the current operation, but there is none open.
 */
final class TransactionRequired extends \LogicException implements ORMException
{
    /**
     * @return TransactionRequired
     */
    public static function new()
    {
        return new self('An open transaction is required for this operation.');
    }
}
