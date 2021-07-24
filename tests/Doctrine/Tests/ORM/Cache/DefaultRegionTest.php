<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use BadMethodCallException;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function assert;
use function class_exists;

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
        self::assertEquals('default.region.test', $this->region->getName());
        self::assertSame($this->cache, $this->region->getCache());
    }

    public function testSharedRegion(): void
    {
        if (! class_exists(ArrayCache::class)) {
            self::markTestSkipped('Test only applies with doctrine/cache 1.x');
        }

        $cache   = new SharedArrayCache();
        $key     = new CacheKeyMock('key');
        $entry   = new CacheEntryMock(['value' => 'foo']);
        $region1 = new DefaultRegion('region1', $cache->createChild());
        $region2 = new DefaultRegion('region2', $cache->createChild());

        self::assertFalse($region1->contains($key));
        self::assertFalse($region2->contains($key));

        $region1->put($key, $entry);
        $region2->put($key, $entry);

        self::assertTrue($region1->contains($key));
        self::assertTrue($region2->contains($key));

        $region1->evictAll();

        self::assertFalse($region1->contains($key));
        self::assertTrue($region2->contains($key));
    }

    public function testDoesNotModifyCacheNamespace(): void
    {
        $cache = DoctrineProvider::wrap(new ArrayAdapter());

        $cache->setNamespace('foo');

        new DefaultRegion('bar', $cache);
        new DefaultRegion('baz', $cache);

        self::assertSame('foo', $cache->getNamespace());
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

        self::assertFalse($this->region->contains($key1));
        self::assertFalse($this->region->contains($key2));

        $this->region->put($key1, $value1);
        $this->region->put($key2, $value2);

        self::assertTrue($this->region->contains($key1));
        self::assertTrue($this->region->contains($key2));

        $actual = $this->region->getMultiple(new CollectionCacheEntry([$key1, $key2]));

        self::assertEquals($value1, $actual[0]);
        self::assertEquals($value2, $actual[1]);
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

if (class_exists(ArrayCache::class)) {
    /**
     * Cache provider that offers child cache items (sharing the same array)
     *
     * Declared as a different class for readability purposes and kept in this file
     * to keep its monstrosity contained.
     *
     * @internal
     */
    final class SharedArrayCache extends ArrayCache
    {
        public function createChild(): Cache
        {
            return new class ($this) extends CacheProvider {
                /** @var ArrayCache */
                private $parent;

                public function __construct(ArrayCache $parent)
                {
                    $this->parent = $parent;
                }

                /**
                 * {@inheritDoc}
                 */
                protected function doFetch($id)
                {
                    return $this->parent->doFetch($id);
                }

                /**
                 * {@inheritDoc}
                 */
                protected function doContains($id)
                {
                    return $this->parent->doContains($id);
                }

                /**
                 * {@inheritDoc}
                 */
                protected function doSave($id, $data, $lifeTime = 0)
                {
                    return $this->parent->doSave($id, $data, $lifeTime);
                }

                /**
                 * {@inheritDoc}
                 */
                protected function doDelete($id)
                {
                    return $this->parent->doDelete($id);
                }

                /**
                 * {@inheritDoc}
                 */
                protected function doFlush()
                {
                    return $this->parent->doFlush();
                }

                /**
                 * {@inheritDoc}
                 */
                protected function doGetStats()
                {
                    return $this->parent->doGetStats();
                }
            };
        }
    }
}
