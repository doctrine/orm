<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Closure;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Traversable;

/**
 * @internal this class is used as a workaround for a cache issue.
 *
 * @see https://github.com/doctrine/orm/pull/10095
 */
final class NullCacheItemPool implements CacheItemPoolInterface
{
    /** @var Closure|null */
    private static $createCacheItem;

    public function __construct()
    {
        self::$createCacheItem ?? self::$createCacheItem = Closure::bind(
            static function (string $key) {
                return new NullCacheItem();
            },
            null,
            NullCacheItem::class
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key): CacheItemInterface
    {
        return (self::$createCacheItem)($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): Traversable
    {
        return $this->generateItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key): bool
    {
        return false;
    }

    public function clear(string $prefix = ''): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    /**
     * @param array<string> $keys
     *
     * @return Traversable<NullCacheItem>
     */
    private function generateItems(array $keys): Traversable
    {
        $f = self::$createCacheItem;

        foreach ($keys as $key) {
            yield $key => $f($key);
        }
    }
}
