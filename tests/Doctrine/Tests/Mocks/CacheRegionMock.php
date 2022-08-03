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
    public $calls = [];

    /** @var array<string, mixed> */
    public $returns = [];

    /** @var string */
    public $name;

    /**
     * Queue a return value for a specific method invocation
     *
     * @param mixed $value
     */
    public function addReturn(string $method, $value): void
    {
        $this->returns[$method][] = $value;
    }

    /**
     * Dequeue a value for a specific method invocation
     *
     * @param mixed $default
     *
     * @return mixed
     */
    private function getReturn(string $method, $default)
    {
        if (isset($this->returns[$method]) && ! empty($this->returns[$method])) {
            return array_shift($this->returns[$method]);
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        $this->calls[__FUNCTION__][] = [];

        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function contains(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];

        return $this->getReturn(__FUNCTION__, false);
    }

    /**
     * {@inheritdoc}
     */
    public function evict(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];

        return $this->getReturn(__FUNCTION__, true);
    }

    /**
     * {@inheritdoc}
     */
    public function evictAll()
    {
        $this->calls[__FUNCTION__][] = [];

        return $this->getReturn(__FUNCTION__, true);
    }

    /**
     * {@inheritdoc}
     */
    public function get(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];

        return $this->getReturn(__FUNCTION__, null);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(CollectionCacheEntry $collection)
    {
        $this->calls[__FUNCTION__][] = ['collection' => $collection];

        return $this->getReturn(__FUNCTION__, null);
    }

    /**
     * {@inheritdoc}
     */
    public function put(CacheKey $key, CacheEntry $entry, ?Lock $lock = null)
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
