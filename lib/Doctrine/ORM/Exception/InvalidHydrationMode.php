<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Doctrine\ORM\Exception\ManagerException;

final class InvalidHydrationMode extends \Exception implements ManagerException
{
    public static function fromMode(string $mode) : self
    {
        return new self("'$mode' is an invalid hydration mode.");
    }
}
