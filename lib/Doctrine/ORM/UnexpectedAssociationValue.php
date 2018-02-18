<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\ORM\Cache\CacheException;

final class UnexpectedAssociationValue extends \Exception implements CacheException
{
    public static function create(
        string $class,
        string $association,
        string $given,
        string $expected
    ) : self {
        return new self(sprintf(
            'Found entity of type %s on association %s#%s, but expecting %s',
            $given,
            $class,
            $association,
            $expected
        ));
    }
}
