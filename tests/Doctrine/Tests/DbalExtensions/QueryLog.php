<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalExtensions;

final class QueryLog
{
    public bool $enabled = false;

    /** @var list<array{sql: string, params: array|null, types: array|null}> */
    public array $queries = [];

    public function logQuery(string $sql, ?array $params = null, ?array $types = null): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
        ];
    }

    /**
     * @return $this
     */
    public function reset(): self
    {
        $this->enabled = false;
        $this->queries = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }
}
