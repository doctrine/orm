<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\Region\DefaultMultiGetRegion;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;

/** @extends RegionTestCase<DefaultMultiGetRegion> */
class MultiGetRegionTest extends RegionTestCase
{
    protected function createRegion(): Region
    {
        return new DefaultMultiGetRegion('default.region.test', $this->cacheItemPool);
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
    public function corruptedDataDoesNotLeakIntoApplication(): void
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
