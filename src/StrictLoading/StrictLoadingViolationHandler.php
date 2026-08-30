<?php

declare(strict_types=1);

namespace Doctrine\ORM\StrictLoading;

/**
 * Decides what happens when strict loading detects a lazy load.
 *
 * Implementations either throw (see {@see ThrowOnViolation}) or record the
 * violation and let the ORM proceed with the load (see {@see LogViolation}).
 */
interface StrictLoadingViolationHandler
{
    public function violation(LazyLoad $lazyLoad, StrictLoadingMode $mode): void;
}
