<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\ORM\Cache\Region\DefaultMultiGetRegion;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\EntityCacheKey;

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
        $value1 = new CacheEntryMock(array('id' => 1, 'name' => 'bar'));

        $key2 = new CacheKeyMock('key.2');
        $value2 = new CacheEntryMock(array('id' => 2, 'name' => 'bar'));

        $this->assertFalse($this->region->contains($key1));
        $this->assertFalse($this->region->contains($key2));

        $this->region->put($key1, $value1);
        $this->region->put($key2, $value2);

        $this->assertTrue($this->region->contains($key1));
        $this->assertTrue($this->region->contains($key2));

        $actual = $this->region->getMultiple(new CollectionCacheEntry(array($key1, $key2)));

        $this->assertEquals($value1, $actual[0]);
        $this->assertEquals($value2, $actual[1]);
    }
}
