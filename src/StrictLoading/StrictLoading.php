<?php

declare(strict_types=1);

namespace Doctrine\ORM\StrictLoading;

/**
 * Runtime configuration and bookkeeping for strict loading.
 *
 * Strict loading turns implicit database access - lazily initialized entities
 * and collections - into a reported violation. It is meant to be switched on
 * for the part of a request that must not query the database anymore (template
 * rendering, serialization, …) and for test suites, so that N+1 queries are
 * caught while writing the code instead of in production.
 *
 * The object is intentionally mutable: it is retrieved from
 * {@see \Doctrine\ORM\Configuration::getStrictLoading()} and can be
 * reconfigured at any point during a request.
 */
final class StrictLoading
{
    private StrictLoadingViolationHandler $violationHandler;

    /**
     * Number of nested scopes that suspend strict loading.
     *
     * ORM internals (flushing, cascades, explicit initialization) suspend
     * strict loading, and so does {@see self::allow()}.
     */
    private int $suspended = 0;

    /** @var array<string,int> Lazy load signatures seen in the current scope, see {@see LazyLoad::signature()}. */
    private array $seen = [];

    public function __construct(
        private StrictLoadingMode $mode = StrictLoadingMode::Disabled,
        StrictLoadingViolationHandler|null $violationHandler = null,
    ) {
        $this->violationHandler = $violationHandler ?? new ThrowOnViolation();
    }

    public function getMode(): StrictLoadingMode
    {
        return $this->mode;
    }

    public function setMode(StrictLoadingMode $mode): void
    {
        $this->mode = $mode;
    }

    public function getViolationHandler(): StrictLoadingViolationHandler
    {
        return $this->violationHandler;
    }

    public function setViolationHandler(StrictLoadingViolationHandler $violationHandler): void
    {
        $this->violationHandler = $violationHandler;
    }

    /** Whether lazy loads are currently reported. */
    public function isActive(): bool
    {
        return $this->mode !== StrictLoadingMode::Disabled && $this->suspended === 0;
    }

    /**
     * Runs the given callback with strict loading temporarily switched off.
     *
     * This is the escape hatch for code that knowingly lazy loads, e.g. a
     * fallback path or a legacy corner of an application that is not worth
     * rewriting yet.
     *
     * @param callable():T $callback
     *
     * @return T
     *
     * @template T
     */
    public function allow(callable $callback): mixed
    {
        $this->suspend();

        try {
            return $callback();
        } finally {
            $this->resume();
        }
    }

    /**
     * Forgets which lazy loads have been seen, which starts a new scope for
     * {@see StrictLoadingMode::NPlusOneOnly}.
     */
    public function reset(): void
    {
        $this->seen = [];
    }

    /**
     * INTERNAL: suspends strict loading until the matching {@see self::resume()}.
     *
     * @internal
     */
    public function suspend(): void
    {
        ++$this->suspended;
    }

    /**
     * INTERNAL: ends a scope opened by {@see self::suspend()}.
     *
     * @internal
     */
    public function resume(): void
    {
        if ($this->suspended > 0) {
            --$this->suspended;
        }
    }

    /**
     * INTERNAL: reports a lazy load the ORM is about to perform.
     *
     * Depending on the configured mode and violation handler this either
     * returns (the load may happen), or the handler throws.
     *
     * @internal called by the ORM at every lazy loading entry point
     */
    public function check(LazyLoad $lazyLoad): void
    {
        if (! $this->isActive()) {
            return;
        }

        if ($this->mode === StrictLoadingMode::NPlusOneOnly) {
            $signature = $lazyLoad->signature();

            if (! isset($this->seen[$signature])) {
                $this->seen[$signature] = 1;

                return;
            }

            ++$this->seen[$signature];
        }

        // Do not report violations that happen while reporting a violation.
        $this->suspend();

        try {
            $this->violationHandler->violation($lazyLoad, $this->mode);
        } finally {
            $this->resume();
        }
    }
}
