<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\ORM\Cache\Region\DefaultMultiGetRegion;
use Doctrine\ORM\Cache\CollectionCacheEntry;

/**
 * @author  Asmir Mustafic <goetas@gmail.com>
 */
class MultiGetRegionTest extends AbstractRegionTest
{
    protected function createRegion()
    {
        return new DefaultMultiGetRegion('default.region.test', $this->cache);
    }

    public function testGetMulti()
    {
        $key1 = new CacheKeyMock('key.1');
        $value1 = new CacheEntryMock(['id' => 1, 'name' => 'bar']);

        $key2 = new CacheKeyMock('key.2');
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
}
