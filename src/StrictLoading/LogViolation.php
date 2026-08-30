<?php

declare(strict_types=1);

namespace Doctrine\ORM\StrictLoading;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Logs strict loading violations and lets the lazy load happen.
 *
 * Use this to introduce strict loading into an existing application without
 * breaking it, e.g. in production while {@see ThrowOnViolation} is used in
 * development and in the test suite.
 */
final class LogViolation implements StrictLoadingViolationHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $level = LogLevel::WARNING,
    ) {
    }

    public function violation(LazyLoad $lazyLoad, StrictLoadingMode $mode): void
    {
        $this->logger->log($this->level, 'Strict loading violation: lazily loading {load}.', [
            'load' => $lazyLoad->describe(),
            'kind' => $lazyLoad->kind->value,
            'class' => $lazyLoad->className,
            'field' => $lazyLoad->fieldName,
            'mode' => $mode->name,
        ]);
    }
}
