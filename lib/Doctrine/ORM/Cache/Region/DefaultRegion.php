<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Region;

use Closure;
use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Traversable;

use function array_map;
use function iterator_to_array;
use function strtr;

/**
 * The simplest cache region compatible with all doctrine-cache drivers.
 */
class DefaultRegion implements Region
{
    private const REGION_KEY_SEPARATOR = '_';
    private const REGION_PREFIX        = 'DC2_REGION_';

    private string $name;
    private int $lifetime = 0;
    private CacheItemPoolInterface $cacheItemPool;

    public function __construct(string $name, CacheItemPoolInterface $cacheItemPool, int $lifetime = 0)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->name          = $name;
        $this->lifetime      = $lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function contains(CacheKey $key)
    {
        return $this->cacheItemPool->hasItem($this->getCacheEntryKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function get(CacheKey $key)
    {
        $item  = $this->cacheItemPool->getItem($this->getCacheEntryKey($key));
        $entry = $item->isHit() ? $item->get() : null;

        if (! $entry instanceof CacheEntry) {
            return null;
        }

        return $entry;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(CollectionCacheEntry $collection)
    {
        $keys = array_map(
            Closure::fromCallable([$this, 'getCacheEntryKey']),
            $collection->identifiers
        );
        /** @var iterable<string, CacheItemInterface> $items */
        $items = $this->cacheItemPool->getItems($keys);
        if ($items instanceof Traversable) {
            $items = iterator_to_array($items);
        }

        $result = [];
        foreach ($keys as $arrayKey => $cacheKey) {
            if (! isset($items[$cacheKey]) || ! $items[$cacheKey]->isHit()) {
                return null;
            }

            $entry = $items[$cacheKey]->get();
            if (! $entry instanceof CacheEntry) {
                return null;
            }

            $result[$arrayKey] = $entry;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function put(CacheKey $key, CacheEntry $entry, ?Lock $lock = null)
    {
        $item = $this->cacheItemPool
            ->getItem($this->getCacheEntryKey($key))
            ->set($entry);

        if ($this->lifetime > 0) {
            $item->expiresAfter($this->lifetime);
        }

        return $this->cacheItemPool->save($item);
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function evict(CacheKey $key)
    {
        return $this->cacheItemPool->deleteItem($this->getCacheEntryKey($key));
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function evictAll()
    {
        return $this->cacheItemPool->clear(self::REGION_PREFIX . $this->name);
    }

    private function getCacheEntryKey(CacheKey $key): string
    {
        return self::REGION_PREFIX . $this->name . self::REGION_KEY_SEPARATOR . strtr($key->hash, '{}()/\@:', '________');
    }
}
