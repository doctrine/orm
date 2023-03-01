<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\Exception\ORMException;
use Exception;

use function sprintf;

class PersisterException extends Exception implements ORMException
{
    public static function matchingAssocationFieldRequiresObject(string $class, string $associationName): PersisterException
    {
        return new self(sprintf(
            'Cannot match on %s::%s with a non-object value. Matching objects by id is ' .
            'not compatible with matching on an in-memory collection, which compares objects by reference.',
            $class,
            $associationName,
        ));
    }
}
