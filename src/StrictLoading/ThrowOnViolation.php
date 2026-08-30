<?php

declare(strict_types=1);

namespace Doctrine\ORM\StrictLoading;

use Doctrine\ORM\Exception\StrictLoadingViolation;

/** Turns every strict loading violation into an exception. */
final class ThrowOnViolation implements StrictLoadingViolationHandler
{
    public function violation(LazyLoad $lazyLoad, StrictLoadingMode $mode): void
    {
        throw StrictLoadingViolation::fromLazyLoad($lazyLoad, $mode);
    }
}
