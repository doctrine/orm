<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalExtensions;

use Doctrine\DBAL\Logging\SQLLogger;

final class LegacySqlLogger implements SQLLogger
{
    /** @var QueryLog */
    private $queryLog;

    public function __construct(QueryLog $queryLog)
    {
        $this->queryLog = $queryLog;
    }

    public function startQuery($sql, ?array $params = null, ?array $types = null): void
    {
        $this->queryLog->logQuery($sql, $params, $types);
    }

    public function stopQuery(): void
    {
    }
}
