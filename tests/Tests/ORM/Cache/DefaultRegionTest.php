<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Cache\Region\UpdateTimestampCache;
use Doctrine\ORM\Cache\TimestampCacheKey;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

use function array_map;
use function is_string;

/** @extends RegionTestCase<DefaultRegion> */
#[Group('DDC-2183')]
class DefaultRegionTest extends RegionTestCase
{
    protected function createRegion(): DefaultRegion
    {
        return new DefaultRegion('default.region.test', $this->cacheItemPool);
    }

    public function testGetters(): void
    {
        self::assertEquals('default.region.test', $this->region->getName());
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

    public function testEvictAllPreservesRegionsWithSharedPrefix(): void
    {
        $neighbor = new DefaultRegion($this->region->getName() . '_children', $this->cacheItemPool);
        $key      = new CacheKeyMock('key');
        $entry    = new CacheEntryMock(['value' => 'foo']);
        $this->region->put($key, $entry);
        $neighbor->put($key, $entry);

        self::assertTrue($this->region->evictAll());
        self::assertFalse($this->region->contains($key));
        self::assertEquals($entry, $neighbor->get($key));
    }

    public function testEvictAllWithPlainPsr6PoolPreservesOtherData(): void
    {
        $pool       = $this->createPsr6Pool();
        $region     = new DefaultRegion('entities', $pool);
        $neighbor   = new DefaultRegion('entities_children', $pool);
        $timestamps = new UpdateTimestampCache('timestamps', $pool);
        $key        = new CacheKeyMock('key');
        $timestamp  = new TimestampCacheKey('Foo');
        $entry      = new CacheEntryMock(['value' => 'foo']);
        $region->put($key, $entry);
        $neighbor->put($key, $entry);
        $timestamps->update($timestamp);
        $pool->save($pool->getItem('unrelated')->set('application value'));

        self::assertTrue($region->evictAll());
        self::assertNull($region->get($key));
        self::assertEquals($entry, $neighbor->get($key));
        self::assertNotNull($timestamps->get($timestamp));
        self::assertSame('application value', $pool->getItem('unrelated')->get());
    }

    public function testEvictionIsVisibleToExistingAndNewInstances(): void
    {
        $key   = new CacheKeyMock('key');
        $entry = new CacheEntryMock(['value' => 'before']);
        $other = new DefaultRegion($this->region->getName(), $this->cacheItemPool);
        $this->region->put($key, $entry);
        self::assertEquals($entry, $other->get($key));

        self::assertTrue($other->evictAll());
        self::assertFalse($this->region->contains($key));
        self::assertNull($this->region->get($key));
        self::assertNull($this->region->getMultiple(new CollectionCacheEntry([$key])));
        $fresh = new DefaultRegion($this->region->getName(), $this->cacheItemPool);
        self::assertNull($fresh->get($key));

        $entry = new CacheEntryMock(['value' => 'after']);
        self::assertTrue($fresh->put($key, $entry));
        self::assertEquals($entry, $this->region->get($key));
        self::assertEquals($entry, $other->get($key));
        self::assertTrue($other->evict($key));
        self::assertNull($fresh->get($key));
    }

    public function testLostRegionVersionDoesNotResurrectEntries(): void
    {
        $versionKey = null;
        $entryKeys  = [];
        $pool       = $this->createPsr6Pool(function (CacheItemInterface $item) use (&$versionKey, &$entryKeys): bool {
            if (is_string($item->get())) {
                $versionKey = $item->getKey();
            } else {
                $entryKeys[] = $item->getKey();
            }

            return $this->cacheItemPool->save($item);
        });
        $region     = new DefaultRegion('entities', $pool);
        $key        = new CacheKeyMock('key');
        $region->put($key, new CacheEntryMock(['value' => 'before eviction']));
        $region->evictAll();
        $region->put($key, new CacheEntryMock(['value' => 'after eviction']));

        self::assertNotNull($versionKey);
        $this->cacheItemPool->deleteItem($versionKey);
        foreach ($entryKeys as $entryKey) {
            self::assertTrue($this->cacheItemPool->hasItem($entryKey));
        }

        self::assertNull($region->get($key));
        self::assertFalse($region->contains($key));
        self::assertNull($region->getMultiple(new CollectionCacheEntry([$key])));
        $fresh = new DefaultRegion('entities', $pool);
        self::assertNull($fresh->get($key));
        $entry = new CacheEntryMock(['value' => 'new namespace']);
        self::assertTrue($fresh->put($key, $entry));
        self::assertEquals($entry, $region->get($key));
    }

    public function testPutStartedBeforeEvictionCannotRestoreOldNamespace(): void
    {
        $other           = new DefaultRegion('entities', $this->cacheItemPool);
        $evictBeforeSave = false;
        $pool            = $this->createPsr6Pool(function (CacheItemInterface $item) use ($other, &$evictBeforeSave): bool {
            if ($evictBeforeSave && $item->get() instanceof CacheEntry) {
                $evictBeforeSave = false;
                self::assertTrue($other->evictAll());
            }

            return $this->cacheItemPool->save($item);
        });
        $region          = new DefaultRegion('entities', $pool);
        $key             = new CacheKeyMock('key');
        $region->put($key, new CacheEntryMock(['value' => 'initial']));
        $evictBeforeSave = true;
        self::assertTrue($region->put($key, new CacheEntryMock(['value' => 'late write'])));
        self::assertNull($region->get($key));
        self::assertNull($other->get($key));

        $entry = new CacheEntryMock(['value' => 'current']);
        self::assertTrue($region->put($key, $entry));
        self::assertEquals($entry, $other->get($key));
    }

    public function testFailedRegionVersionWriteIsReported(): void
    {
        $failWrites = false;
        $pool       = $this->createPsr6Pool(function (CacheItemInterface $item) use (&$failWrites): bool {
            return ! $failWrites && $this->cacheItemPool->save($item);
        });
        $region     = new DefaultRegion('entities', $pool);
        $key        = new CacheKeyMock('key');
        $entry      = new CacheEntryMock(['value' => 'foo']);
        self::assertTrue($region->put($key, $entry));
        $failWrites = true;
        self::assertFalse($region->evictAll());
        self::assertEquals($entry, $region->get($key));
    }

    public function testUnwritableRegionVersionIsNotUsed(): void
    {
        $pool   = $this->createPsr6Pool(static fn (): bool => false);
        $region = new DefaultRegion('entities', $pool);
        $key    = new CacheKeyMock('key');
        self::assertFalse($region->put($key, new CacheEntryMock(['value' => 'foo'])));
        self::assertFalse($region->contains($key));
        self::assertNull($region->get($key));
        self::assertNull($region->getMultiple(new CollectionCacheEntry([$key])));
        self::assertSame([], $region->getMultiple(new CollectionCacheEntry([])));
        self::assertFalse($region->evict($key));
        self::assertFalse($region->evictAll());
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
            $this->region->getMultiple(new CollectionCacheEntry(['one' => $key1, 'two' => $key2])),
        );

        self::assertSame([
            'one' => ['id' => 1],
            'two' => ['id' => 2],
        ], $actual);
    }

    #[Test]
    #[Group('GH7266')]
    public function corruptedDataDoesNotLeakIntoApplicationWhenGettingSingleEntry(): void
    {
        $key1 = new CacheKeyMock('key.1');
        $this->storeCorruptedEntry($key1);

        self::assertTrue($this->region->contains($key1));
        self::assertNull($this->region->get($key1));
    }

    #[Test]
    #[Group('GH7266')]
    public function corruptedDataDoesNotLeakIntoApplicationWhenGettingMultipleEntries(): void
    {
        $key1 = new CacheKeyMock('key.1');
        $this->storeCorruptedEntry($key1);

        self::assertTrue($this->region->contains($key1));
        self::assertNull($this->region->getMultiple(new CollectionCacheEntry([$key1])));
    }

    private function createPsr6Pool(callable|null $save = null): CacheItemPoolInterface
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        foreach (['getItem', 'getItems', 'hasItem', 'deleteItem', 'deleteItems', 'saveDeferred', 'commit'] as $method) {
            $pool->method($method)->willReturnCallback([$this->cacheItemPool, $method]);
        }

        $pool->method('save')->willReturnCallback($save ?? $this->cacheItemPool->save(...));

        // PSR-6 clear() clears the entire pool; it has no prefix argument.
        $pool->method('clear')->willReturnCallback(fn (): bool => $this->cacheItemPool->clear());

        return $pool;
    }

    private function storeCorruptedEntry(CacheKeyMock $key): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        foreach (['getItem', 'getItems', 'hasItem'] as $method) {
            $pool->method($method)->willReturnCallback([$this->cacheItemPool, $method]);
        }

        $pool->method('save')->willReturnCallback(function (CacheItemInterface $item): bool {
            if ($item->get() instanceof CacheEntry) {
                $item->set('a-very-invalid-value');
            }

            return $this->cacheItemPool->save($item);
        });

        $this->region = new DefaultRegion($this->region->getName(), $pool);
        $this->region->put($key, new CacheEntryMock(['value' => 'foo']));
    }
}
