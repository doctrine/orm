<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * Cache regions configuration
 */
class RegionsConfiguration
{
    /** @var array<string,int> */
    private array $lifetimes = [];

    /** @var array<string,int> */
    private array $lockLifetimes = [];

    public function __construct(
        private int $defaultLifetime = 3600,
        private int $defaultLockLifetime = 60,
    ) {
    }

    public function getDefaultLifetime(): int
    {
        return $this->defaultLifetime;
    }

    public function setDefaultLifetime(int $defaultLifetime): void
    {
        $this->defaultLifetime = $defaultLifetime;
    }

    public function getDefaultLockLifetime(): int
    {
        return $this->defaultLockLifetime;
    }

    public function setDefaultLockLifetime(int $defaultLockLifetime): void
    {
        $this->defaultLockLifetime = $defaultLockLifetime;
    }

    public function getLifetime(string $regionName): int
    {
        return $this->lifetimes[$regionName] ?? $this->defaultLifetime;
    }

    public function setLifetime(string $name, int $lifetime): void
    {
        $this->lifetimes[$name] = $lifetime;
    }

    public function getLockLifetime(string $regionName): int
    {
        return $this->lockLifetimes[$regionName] ?? $this->defaultLockLifetime;
    }

    public function setLockLifetime(string $name, int $lifetime): void
    {
        $this->lockLifetimes[$name] = $lifetime;
    }
}
