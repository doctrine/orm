<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Filter\SQLFilter;
use InvalidArgumentException;

use function assert;
use function ksort;

/**
 * Collection class for all the query filters.
 */
class FilterCollection
{
    /* Filter STATES */

    /**
     * A filter object is in CLEAN state when it has no changed parameters.
     */
    public const FILTERS_STATE_CLEAN = 1;

    /**
     * A filter object is in DIRTY state when it has changed parameters.
     */
    public const FILTERS_STATE_DIRTY = 2;

    private readonly Configuration $config;

    /**
     * Instances of enabled filters.
     *
     * @var array<string, SQLFilter>
     */
    private array $enabledFilters = [];

    /** The filter hash from the last time the query was parsed. */
    private string $filterHash = '';

    /**
     * The current state of this filter.
     *
     * @psalm-var self::FILTERS_STATE_*
     */
    private int $filtersState = self::FILTERS_STATE_CLEAN;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        $this->config = $em->getConfiguration();
    }

    /**
     * Gets all the enabled filters.
     *
     * @return array<string, SQLFilter> The enabled filters.
     */
    public function getEnabledFilters(): array
    {
        return $this->enabledFilters;
    }

    /**
     * Enables a filter from the collection.
     *
     * @throws InvalidArgumentException If the filter does not exist.
     */
    public function enable(string $name): SQLFilter
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException("Filter '" . $name . "' does not exist.");
        }

        if (! $this->isEnabled($name)) {
            $filterClass = $this->config->getFilterClassName($name);

            assert($filterClass !== null);

            $this->enabledFilters[$name] = new $filterClass($this->em);

            // Keep the enabled filters sorted for the hash
            ksort($this->enabledFilters);

            $this->setFiltersStateDirty();
        }

        return $this->enabledFilters[$name];
    }

    /**
     * Disables a filter.
     *
     * @throws InvalidArgumentException If the filter does not exist.
     */
    public function disable(string $name): SQLFilter
    {
        // Get the filter to return it
        $filter = $this->getFilter($name);

        unset($this->enabledFilters[$name]);

        $this->setFiltersStateDirty();

        return $filter;
    }

    /**
     * Gets an enabled filter from the collection.
     *
     * @throws InvalidArgumentException If the filter is not enabled.
     */
    public function getFilter(string $name): SQLFilter
    {
        if (! $this->isEnabled($name)) {
            throw new InvalidArgumentException("Filter '" . $name . "' is not enabled.");
        }

        return $this->enabledFilters[$name];
    }

    /**
     * Checks whether filter with given name is defined.
     */
    public function has(string $name): bool
    {
        return $this->config->getFilterClassName($name) !== null;
    }

    /**
     * Checks if a filter is enabled.
     */
    public function isEnabled(string $name): bool
    {
        return isset($this->enabledFilters[$name]);
    }

    /**
     * Checks if the filter collection is clean.
     */
    public function isClean(): bool
    {
        return $this->filtersState === self::FILTERS_STATE_CLEAN;
    }

    /**
     * Generates a string of currently enabled filters to use for the cache id.
     */
    public function getHash(): string
    {
        // If there are only clean filters, the previous hash can be returned
        if ($this->filtersState === self::FILTERS_STATE_CLEAN) {
            return $this->filterHash;
        }

        $filterHash = '';

        foreach ($this->enabledFilters as $name => $filter) {
            $filterHash .= $name . $filter;
        }

        $this->filterHash   = $filterHash;
        $this->filtersState = self::FILTERS_STATE_CLEAN;

        return $filterHash;
    }

    /**
     * Sets the filter state to dirty.
     */
    public function setFiltersStateDirty(): void
    {
        $this->filtersState = self::FILTERS_STATE_DIRTY;
    }
}
