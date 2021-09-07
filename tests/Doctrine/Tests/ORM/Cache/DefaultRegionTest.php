<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use BadMethodCallException;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function assert;

/**
 * @group DDC-2183
 */
class DefaultRegionTest extends AbstractRegionTest
{
    protected function createRegion(): Region
    {
        return new DefaultRegion('default.region.test', $this->cache);
    }

    public function testGetters(): void
    {
        $this->assertEquals('default.region.test', $this->region->getName());
        $this->assertSame($this->cache, $this->region->getCache());
    }

    public function testSharedRegion(): void
    {
        $cache   = new SharedArrayCache();
        $key     = new CacheKeyMock('key');
        $entry   = new CacheEntryMock(['value' => 'foo']);
        $region1 = new DefaultRegion('region1', DoctrineProvider::wrap($cache->createChild()));
        $region2 = new DefaultRegion('region2', DoctrineProvider::wrap($cache->createChild()));

        $this->assertFalse($region1->contains($key));
        $this->assertFalse($region2->contains($key));

        $region1->put($key, $entry);
        $region2->put($key, $entry);

        $this->assertTrue($region1->contains($key));
        $this->assertTrue($region2->contains($key));

        $region1->evictAll();

        $this->assertFalse($region1->contains($key));
        $this->assertTrue($region2->contains($key));
    }

    public function testDoesNotModifyCacheNamespace(): void
    {
        $cache = DoctrineProvider::wrap(new ArrayAdapter());

        $cache->setNamespace('foo');

        new DefaultRegion('bar', $cache);
        new DefaultRegion('baz', $cache);

        $this->assertSame('foo', $cache->getNamespace());
    }

    public function testEvictAllWithGenericCacheThrowsUnsupportedException(): void
    {
        $cache = $this->createMock(Cache::class);
        assert($cache instanceof Cache);

        $region = new DefaultRegion('foo', $cache);

        $this->expectException(BadMethodCallException::class);

        $region->evictAll();
    }

    public function testGetMulti(): void
    {
        $key1   = new CacheKeyMock('key.1');
        $value1 = new CacheEntryMock(['id' => 1, 'name' => 'bar']);

        $key2   = new CacheKeyMock('key.2');
        $value2 = new CacheEntryMock(['id' => 2, 'name' => 'bar']);

        $this->assertFalse($this->region->contains($key1));
        $this->assertFalse($this->region->contains($key2));

        $this->region->put($key1, $value1);
        $this->region->put($key2, $value2);

        $this->assertTrue($this->region->contains($key1));
        $this->assertTrue($this->region->contains($key2));

        $actual = $this->region->getMultiple(new CollectionCacheEntry([$key1, $key2]));

        $this->assertEquals($value1, $actual[0]);
        $this->assertEquals($value2, $actual[1]);
    }

    /**
     * @test
     * @group GH7266
     */
    public function corruptedDataDoesNotLeakIntoApplicationWhenGettingSingleEntry(): void
    {
        $key1 = new CacheKeyMock('key.1');
        $this->cache->save($this->region->getName() . '_' . $key1->hash, 'a-very-invalid-value');

        self::assertTrue($this->region->contains($key1));
        self::assertNull($this->region->get($key1));
    }

    /**
     * @test
     * @group GH7266
     */
    public function corruptedDataDoesNotLeakIntoApplicationWhenGettingMultipleEntries(): void
    {
        $key1 = new CacheKeyMock('key.1');
        $this->cache->save($this->region->getName() . '_' . $key1->hash, 'a-very-invalid-value');

        self::assertTrue($this->region->contains($key1));
        self::assertNull($this->region->getMultiple(new CollectionCacheEntry([$key1])));
    }
}

/**
 * Cache provider that offers child cache items (sharing the same array)
 *
 * Declared as a different class for readability purposes and kept in this file
 * to keep its monstrosity contained.
 *
 * @internal
 */
final class SharedArrayCache extends ArrayAdapter
{
    public function createChild(): CacheItemPoolInterface
    {
        return new class ($this) implements CacheItemPoolInterface {
            /** @var CacheItemPoolInterface */
            private $parent;

            public function __construct(CacheItemPoolInterface $parent)
            {
                $this->parent = $parent;
            }

            public function getItem($key): CacheItemInterface
            {
                return $this->parent->getItem($key);
            }

            public function getItems(array $keys = []): iterable
            {
                return $this->parent->getItems($keys);
            }

            public function hasItem($key): bool
            {
                return $this->parent->hasItem($key);
            }

            public function clear(): bool
            {
                return $this->parent->clear();
            }

            public function deleteItem($key): bool
            {
                return $this->parent->deleteItem($key);
            }

            public function deleteItems(array $keys): bool
            {
                return $this->parent->deleteItems($keys);
            }

            public function save(CacheItemInterface $item): bool
            {
                return $this->parent->save($item);
            }

            public function saveDeferred(CacheItemInterface $item): bool
            {
                return $this->parent->saveDeferred($item);
            }

            public function commit(): bool
            {
                return $this->parent->commit();
            }
        };
    }
}
