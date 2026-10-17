<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Region;

use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Traversable;

use function array_map;
use function bin2hex;
use function ctype_xdigit;
use function hash;
use function is_string;
use function iterator_to_array;
use function random_bytes;
use function strlen;
use function strtr;

/**
 * The simplest cache region compatible with all doctrine-cache drivers.
 */
class DefaultRegion implements Region
{
    private const REGION_KEY_SEPARATOR = '_';
    private const REGION_PREFIX        = 'DC2_L2_REGION_';

    public function __construct(
        private readonly string $name,
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly int $lifetime = 0,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function contains(CacheKey $key): bool
    {
        $version = $this->getRegionVersion();

        return $version !== null && $this->cacheItemPool->hasItem($this->getCacheEntryKey($key, $version));
    }

    public function get(CacheKey $key): CacheEntry|null
    {
        $version = $this->getRegionVersion();
        if ($version === null) {
            return null;
        }

        $item  = $this->cacheItemPool->getItem($this->getCacheEntryKey($key, $version));
        $entry = $item->isHit() ? $item->get() : null;

        if (! $entry instanceof CacheEntry) {
            return null;
        }

        return $entry;
    }

    public function getMultiple(CollectionCacheEntry $collection): array|null
    {
        if ($collection->identifiers === []) {
            return [];
        }

        $version = $this->getRegionVersion();
        if ($version === null) {
            return null;
        }

        $keys = array_map(
            fn (CacheKey $key): string => $this->getCacheEntryKey($key, $version),
            $collection->identifiers,
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

    public function put(CacheKey $key, CacheEntry $entry, Lock|null $lock = null): bool
    {
        $version = $this->getRegionVersion();
        if ($version === null) {
            return false;
        }

        $item = $this->cacheItemPool
            ->getItem($this->getCacheEntryKey($key, $version))
            ->set($entry);

        if ($this->lifetime > 0) {
            $item->expiresAfter($this->lifetime);
        }

        return $this->cacheItemPool->save($item);
    }

    public function evict(CacheKey $key): bool
    {
        $version = $this->getRegionVersion();

        return $version !== null && $this->cacheItemPool->deleteItem($this->getCacheEntryKey($key, $version));
    }

    public function evictAll(): bool
    {
        return $this->replaceRegionVersion($this->cacheItemPool->getItem($this->getRegionVersionKey())) !== null;
    }

    private function getCacheEntryKey(CacheKey $key, string $version): string
    {
        return self::REGION_PREFIX . $version . self::REGION_KEY_SEPARATOR . strtr($key->hash, '{}()/\@:', '________');
    }

    private function getRegionVersionKey(): string
    {
        return self::REGION_PREFIX . 'version_' . hash('sha256', $this->name);
    }

    private function getRegionVersion(): string|null
    {
        // Read on every operation so eviction by another instance takes effect.
        $item    = $this->cacheItemPool->getItem($this->getRegionVersionKey());
        $version = $item->isHit() ? $item->get() : null;
        if (is_string($version) && strlen($version) === 32 && ctype_xdigit($version)) {
            return $version;
        }

        // Never reuse a missing version: its old entries may still exist.
        return $this->replaceRegionVersion($item);
    }

    private function replaceRegionVersion(CacheItemInterface $item): string|null
    {
        $version = bin2hex(random_bytes(16));
        $item->set($version)->expiresAfter(null);

        return $this->cacheItemPool->save($item) ? $version : null;
    }
}
