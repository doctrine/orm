<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use RuntimeException;

/** @deprecated */
final class EntityManagerClosed extends RuntimeException implements ManagerException
{
    public static function create(): self
    {
        return new self('The EntityManager is closed. Call clear() to reopen it.');
    }
}
