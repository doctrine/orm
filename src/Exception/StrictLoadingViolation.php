<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Doctrine\ORM\StrictLoading\LazyLoad;
use Doctrine\ORM\StrictLoading\StrictLoadingMode;
use RuntimeException;

use function sprintf;

/**
 * Thrown when strict loading is active and the ORM was about to lazily load
 * data from the database.
 */
final class StrictLoadingViolation extends RuntimeException implements ORMException
{
    private function __construct(public readonly LazyLoad $lazyLoad, string $message)
    {
        parent::__construct($message);
    }

    public static function fromLazyLoad(LazyLoad $lazyLoad, StrictLoadingMode $mode): self
    {
        return new self($lazyLoad, sprintf(
            'Strict loading violation: lazily loading %s is not allowed here.%s Fetch the data'
            . ' explicitly - with a fetch join, an eager fetch mode, or EntityManager::preload()'
            . ' for entities you already hold - or wrap the offending code in'
            . ' Doctrine\ORM\StrictLoading\StrictLoading::allow().',
            $lazyLoad->describe(),
            $mode === StrictLoadingMode::NPlusOneOnly
                ? ' It was already loaded lazily before in this scope, which makes it an N+1 query.'
                : '',
        ));
    }
}
