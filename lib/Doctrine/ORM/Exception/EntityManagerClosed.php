<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Throwable;

final class EntityManagerClosed extends ORMException implements ManagerException
{
    private const EXCEPTION_CLOSED = 'The EntityManager is closed.';

    public static function create(): self
    {
        return new self(self::EXCEPTION_CLOSED);
    }

    public static function createWithClosingThrowable(Throwable $previousThrowable): self
    {
        return new self(self::EXCEPTION_CLOSED, 0, $previousThrowable);
    }
}
