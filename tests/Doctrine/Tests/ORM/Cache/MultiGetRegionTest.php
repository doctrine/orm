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
    public function corruptedDataDoesNotLeakIntoApplication() : void
    {
        $key1 = new CacheKeyMock('key.1');
        $this->cache->save($this->region->getName() . '_' . $key1->hash, 'a-very-invalid-value');

        self::assertTrue($this->region->contains($key1));
        self::assertNull($this->region->getMultiple(new CollectionCacheEntry([$key1])));
    }
}
