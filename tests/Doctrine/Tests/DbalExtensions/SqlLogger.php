<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalExtensions;

use Psr\Log\AbstractLogger;

final class SqlLogger extends AbstractLogger
{
    /** @var QueryLog */
    private $queryLog;

    public function __construct(QueryLog $queryLog)
    {
        $this->queryLog = $queryLog;
    }

    public function log($level, $message, array $context = []): void
    {
        if (! isset($context['sql'])) {
            return;
        }

        $this->queryLog->logQuery(
            $context['sql'],
            $context['params'] ?? null,
            $context['types'] ?? null
        );
    }
}
