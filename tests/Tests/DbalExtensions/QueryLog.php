<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalExtensions;

final class QueryLog
{
    /** @var list<array{sql: string, params: array|null, types: array|null}> */
    public array $queries = [];

    /** @var list<'to_one'|'collection'|null> */
    public array $lazyLoadKinds = [];

    /** @var (callable(): 'to_one'|'collection'|null)|null */
    private $lazyLoadKindProvider = null;

    private bool $enabled = false;

    public function logQuery(string $sql, array|null $params = null, array|null $types = null): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
        ];

        if ($this->lazyLoadKindProvider !== null) {
            $this->lazyLoadKinds[] = ($this->lazyLoadKindProvider)();
        }
    }

    /**
     * Captures the lazy loading kind for every logged query.
     *
     * @param callable(): 'to_one'|'collection'|null $provider
     *
     * @return $this
     */
    public function captureLazyLoadKind(callable $provider): self
    {
        $this->lazyLoadKindProvider = $provider;
        $this->lazyLoadKinds        = [];

        return $this;
    }

    /** @return $this */
    public function reset(): self
    {
        $this->enabled       = false;
        $this->queries       = [];
        $this->lazyLoadKinds = [];

        return $this;
    }

    /** @return $this */
    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    /** @return $this */
    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }
}
