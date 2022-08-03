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

    public function __construct(
        private readonly string $name,
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly int $lifetime = 0
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function contains(CacheKey $key): bool
    {
        return $this->cacheItemPool->hasItem($this->getCacheEntryKey($key));
    }

    public function get(CacheKey $key): ?CacheEntry
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
    public function getMultiple(CollectionCacheEntry $collection): ?array
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

    public function put(CacheKey $key, CacheEntry $entry, ?Lock $lock = null): bool
    {
        $item = $this->cacheItemPool
            ->getItem($this->getCacheEntryKey($key))
            ->set($entry);

        if ($this->lifetime > 0) {
            $item->expiresAfter($this->lifetime);
        }

        return $this->cacheItemPool->save($item);
    }

    public function evict(CacheKey $key): bool
    {
        return $this->cacheItemPool->deleteItem($this->getCacheEntryKey($key));
    }

    public function evictAll(): bool
    {
        return $this->cacheItemPool->clear(self::REGION_PREFIX . $this->name);
    }

    private function getCacheEntryKey(CacheKey $key): string
    {
        return self::REGION_PREFIX . $this->name . self::REGION_KEY_SEPARATOR . strtr($key->hash, '{}()/\@:', '________');
    }
}
