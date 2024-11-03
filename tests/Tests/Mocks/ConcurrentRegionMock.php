<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\ConcurrentRegion;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\LockException;
use Doctrine\ORM\Cache\Region;
use Exception;

use function array_shift;

/**
 * Concurrent region mock
 *
 * Used to mock a ConcurrentRegion
 */
class ConcurrentRegionMock implements ConcurrentRegion
{
    /** @phpstan-var array<string, list<array<string, mixed>>> */
    public array $calls = [];

    /** @phpstan-var array<string, list<Exception>> */
    public array $exceptions = [];

    /** @phpstan-var array<string, Lock> */
    public array $locks = [];

    public function __construct(
        private readonly Region $region,
    ) {
    }

    /**
     * Dequeue an exception for a specific method invocation
     */
    private function throwException(string $method): void
    {
        if (isset($this->exceptions[$method]) && ! empty($this->exceptions[$method])) {
            $exception = array_shift($this->exceptions[$method]);

            if ($exception !== null) {
                throw $exception;
            }
        }
    }

    /**
     * Queue an exception for the next method invocation
     */
    public function addException(string $method, Exception $e): void
    {
        $this->exceptions[$method][] = $e;
    }

    /**
     * Locks a specific cache entry
     */
    public function setLock(CacheKey $key, Lock $lock): void
    {
        $this->locks[$key->hash] = $lock;
    }

    public function contains(CacheKey $key): bool
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];

        if (isset($this->locks[$key->hash])) {
            return false;
        }

        $this->throwException(__FUNCTION__);

        return $this->region->contains($key);
    }

    public function evict(CacheKey $key): bool
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];

        $this->throwException(__FUNCTION__);

        return $this->region->evict($key);
    }

    public function evictAll(): bool
    {
        $this->calls[__FUNCTION__][] = [];

        $this->throwException(__FUNCTION__);

        return $this->region->evictAll();
    }

    public function get(CacheKey $key): CacheEntry|null
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];

        $this->throwException(__FUNCTION__);

        if (isset($this->locks[$key->hash])) {
            return null;
        }

        return $this->region->get($key);
    }

    public function getMultiple(CollectionCacheEntry $collection): array|null
    {
        $this->calls[__FUNCTION__][] = ['collection' => $collection];

        $this->throwException(__FUNCTION__);

        return $this->region->getMultiple($collection);
    }

    public function getName(): string
    {
        $this->calls[__FUNCTION__][] = [];

        $this->throwException(__FUNCTION__);

        return $this->region->getName();
    }

    public function put(CacheKey $key, CacheEntry $entry, Lock|null $lock = null): bool
    {
        $this->calls[__FUNCTION__][] = ['key' => $key, 'entry' => $entry];

        $this->throwException(__FUNCTION__);

        if (isset($this->locks[$key->hash])) {
            if ($lock !== null && $this->locks[$key->hash]->value === $lock->value) {
                return $this->region->put($key, $entry);
            }

            return false;
        }

        return $this->region->put($key, $entry);
    }

    public function lock(CacheKey $key): Lock|null
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];

        $this->throwException(__FUNCTION__);

        if (isset($this->locks[$key->hash])) {
            return null;
        }

        return $this->locks[$key->hash] = Lock::createLockRead();
    }

    public function unlock(CacheKey $key, Lock $lock): bool
    {
        $this->calls[__FUNCTION__][] = ['key' => $key, 'lock' => $lock];

        $this->throwException(__FUNCTION__);

        if (! isset($this->locks[$key->hash])) {
            return false;
        }

        if ($this->locks[$key->hash]->value !== $lock->value) {
            throw new LockException('unexpected lock value');
        }

        unset($this->locks[$key->hash]);

        return true;
    }
}
