<?php

declare(strict_types=1);

namespace Doctrine\ORM\EntityManager;

use Doctrine\ORM\ManagerException;

final class InvalidHydrationMode extends \Exception implements ManagerException
{
    public static function fromMode(string $mode) : self
    {
        return new self("'$mode' is an invalid hydration mode.");
    }
}
