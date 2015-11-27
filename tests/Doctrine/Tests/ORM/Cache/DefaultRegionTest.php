<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;

/**
 * @group DDC-2183
 */
class DefaultRegionTest extends AbstractRegionTest
{
    protected function createRegion()
    {
        return new DefaultRegion('default.region.test', $this->cache);
    }

    public function testGetters()
    {
        $this->assertEquals('default.region.test', $this->region->getName());
        $this->assertSame($this->cache, $this->region->getCache());
    }

    public function testSharedRegion()
    {
        if ( ! extension_loaded('apc') || false === @apc_cache_info()) {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of APC');
        }

        $key     = new CacheKeyMock('key');
        $entry   = new CacheEntryMock(array('value' => 'foo'));
        $region1 = new DefaultRegion('region1', new \Doctrine\Common\Cache\ApcCache());
        $region2 = new DefaultRegion('region2', new \Doctrine\Common\Cache\ApcCache());

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

    public function testDoesNotModifyCacheNamespace()
    {
        $cache = new ArrayCache();

        $cache->setNamespace('foo');

        new DefaultRegion('bar', $cache);
        new DefaultRegion('baz', $cache);

        $this->assertSame('foo', $cache->getNamespace());
    }

    public function testEvictAllWithGenericCacheThrowsUnsupportedException()
    {
        /* @var $cache \Doctrine\Common\Cache\Cache */
        $cache = $this->getMock('Doctrine\Common\Cache\Cache');

        $region = new DefaultRegion('foo', $cache);

        $this->setExpectedException('BadMethodCallException');

        $region->evictAll();
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