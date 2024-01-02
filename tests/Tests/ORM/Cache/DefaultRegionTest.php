<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function array_map;

/**
 * @extends RegionTestCase<DefaultRegion>
 * @group DDC-2183
 */
class DefaultRegionTest extends RegionTestCase
{
    protected function createRegion(): Region
    {
        return new DefaultRegion('default.region.test', $this->cacheItemPool);
    }

    public function testGetters(): void
    {
        self::assertEquals('default.region.test', $this->region->getName());
        self::assertSame($this->cacheItemPool, $this->region->getCache()->getPool());
    }

    public function testSharedRegion(): void
    {
        $key     = new CacheKeyMock('key');
        $entry   = new CacheEntryMock(['value' => 'foo']);
        $region1 = new DefaultRegion('region1', $this->cacheItemPool);
        $region2 = new DefaultRegion('region2', $this->cacheItemPool);

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

    public function testGetMultiPreservesOrderAndKeys(): void
    {
        $key1   = new CacheKeyMock('key.1');
        $value1 = new CacheEntryMock(['id' => 1]);

        $key2   = new CacheKeyMock('key.2');
        $value2 = new CacheEntryMock(['id' => 2]);

        $this->region->put($key1, $value1);
        $this->region->put($key2, $value2);

        $actual = array_map(
            'iterator_to_array',
            $this->region->getMultiple(new CollectionCacheEntry(['one' => $key1, 'two' => $key2]))
        );

        self::assertSame([
            'one' => ['id' => 1],
            'two' => ['id' => 2],
        ], $actual);
    }

    /**
     * @test
     * @group GH7266
     */
    public function corruptedDataDoesNotLeakIntoApplicationWhenGettingSingleEntry(): void
    {
        $key1 = new CacheKeyMock('key.1');
        $this->cacheItemPool->save(
            $this->cacheItemPool
                ->getItem('DC2_REGION_' . $this->region->getName() . '_' . $key1->hash)
                ->set('a-very-invalid-value')
        );

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
        $this->cacheItemPool->save(
            $this->cacheItemPool
                ->getItem('DC2_REGION_' . $this->region->getName() . '_' . $key1->hash)
                ->set('a-very-invalid-value')
        );

        self::assertTrue($this->region->contains($key1));
        self::assertNull($this->region->getMultiple(new CollectionCacheEntry([$key1])));
    }
}
