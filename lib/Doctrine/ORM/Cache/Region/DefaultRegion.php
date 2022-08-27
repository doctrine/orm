<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Region;

use Closure;
use Doctrine\Common\Cache\Cache as LegacyCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Traversable;
use TypeError;

use function array_map;
use function get_debug_type;
use function iterator_to_array;
use function sprintf;
use function strtr;

/**
 * The simplest cache region compatible with all doctrine-cache drivers.
 */
class DefaultRegion implements Region
{
    /** @internal since 2.11, this constant will be private in 3.0. */
    public const REGION_KEY_SEPARATOR = '_';
    private const REGION_PREFIX       = 'DC2_REGION_';

    /**
     * @deprecated since 2.11, this property will be removed in 3.0.
     *
     * @var LegacyCache
     */
    protected $cache;

    /**
     * @internal since 2.11, this property will be private in 3.0.
     *
     * @var string
     */
    protected $name;

    /**
     * @internal since 2.11, this property will be private in 3.0.
     *
     * @var int
     */
    protected $lifetime = 0;

    /** @var CacheItemPoolInterface */
    private $cacheItemPool;

    /** @param CacheItemPoolInterface $cacheItemPool */
    public function __construct(string $name, $cacheItemPool, int $lifetime = 0)
    {
        if ($cacheItemPool instanceof LegacyCache) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/9322',
                'Passing an instance of %s to %s is deprecated, pass a %s instead.',
                get_debug_type($cacheItemPool),
                __METHOD__,
                CacheItemPoolInterface::class
            );

            $this->cache         = $cacheItemPool;
            $this->cacheItemPool = CacheAdapter::wrap($cacheItemPool);
        } elseif (! $cacheItemPool instanceof CacheItemPoolInterface) {
            throw new TypeError(sprintf(
                '%s: Parameter #2 is expected to be an instance of %s, got %s.',
                __METHOD__,
                CacheItemPoolInterface::class,
                get_debug_type($cacheItemPool)
            ));
        } else {
            $this->cache         = DoctrineProvider::wrap($cacheItemPool);
            $this->cacheItemPool = $cacheItemPool;
        }

        $this->name     = $name;
        $this->lifetime = $lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @deprecated
     *
     * @return CacheProvider
     */
    public function getCache()
    {
        return $this->cache;
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

    /**
     * @internal since 2.11, this method will be private in 3.0.
     *
     * @return string
     */
    protected function getCacheEntryKey(CacheKey $key)
    {
        return self::REGION_PREFIX . $this->name . self::REGION_KEY_SEPARATOR . strtr($key->hash, '{}()/\@:', '________');
    }
}
