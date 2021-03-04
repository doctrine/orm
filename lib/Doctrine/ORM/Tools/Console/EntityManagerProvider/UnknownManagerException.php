<?php

namespace Doctrine\ORM\Tools\Console\EntityManagerProvider;

use OutOfBoundsException;

use function implode;
use function sprintf;

class UnknownManagerException extends OutOfBoundsException
{
    public static function unknownManager(string $unknownManager, array $knownManagers = [])
    {
        return new self(sprintf(
            'Requested unknown entity manager: %s, known managers: %s',
            $unknownManager,
            implode(', ', $knownManagers)
        ));
    }
}
