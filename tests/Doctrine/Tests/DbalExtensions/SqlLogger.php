<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalExtensions;

use Psr\Log\AbstractLogger;

final class SqlLogger extends AbstractLogger
{
    public function __construct(private readonly QueryLog $queryLog)
    {
    }

    public function log($level, $message, array $context = []): void
    {
        if (! isset($context['sql'])) {
            return;
        }

        $this->queryLog->logQuery(
            $context['sql'],
            $context['params'] ?? null,
            $context['types'] ?? null,
        );
    }
}
