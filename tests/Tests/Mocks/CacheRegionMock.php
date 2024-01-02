<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region;

use function array_shift;

/**
 * Cache region mock
 */
class CacheRegionMock implements Region
{
    /** @var array<string, list<array<string, mixed>>> */
    public array $calls = [];

    /** @var array<string, mixed> */
    public array $returns = [];

    public string $name = 'mock';

    /**
     * Queue a return value for a specific method invocation
     */
    public function addReturn(string $method, mixed $value): void
    {
        $this->returns[$method][] = $value;
    }

    /**
     * Dequeue a value for a specific method invocation
     */
    private function getReturn(string $method, mixed $default): mixed
    {
        if (isset($this->returns[$method]) && ! empty($this->returns[$method])) {
            return array_shift($this->returns[$method]);
        }

        return $default;
    }

    public function getName(): string
    {
        $this->calls[__FUNCTION__][] = [];

        return $this->name;
    }

    public function contains(CacheKey $key): bool
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];

        return $this->getReturn(__FUNCTION__, false);
    }

    public function evict(CacheKey $key): bool
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];

        return $this->getReturn(__FUNCTION__, true);
    }

    public function evictAll(): bool
    {
        $this->calls[__FUNCTION__][] = [];

        return $this->getReturn(__FUNCTION__, true);
    }

    public function get(CacheKey $key): CacheEntry|null
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];

        return $this->getReturn(__FUNCTION__, null);
    }

    public function getMultiple(CollectionCacheEntry $collection): array|null
    {
        $this->calls[__FUNCTION__][] = ['collection' => $collection];

        return $this->getReturn(__FUNCTION__, null);
    }

    public function put(CacheKey $key, CacheEntry $entry, Lock|null $lock = null): bool
    {
        $this->calls[__FUNCTION__][] = ['key' => $key, 'entry' => $entry];

        return $this->getReturn(__FUNCTION__, true);
    }

    public function clear(): void
    {
        $this->calls   = [];
        $this->returns = [];
    }
}
