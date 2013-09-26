<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\Common\Cache\ArrayCache;

/**
 * @group DDC-2183
 */
class DefaultRegionTest extends OrmFunctionalTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\Region\DefaultRegion
     */
    private $region;

    /**
     * @var \Doctrine\Common\Cache\ArrayCache
     */
    private $cache;

    protected function setUp()
    {
        parent::setUp();

        $this->cache  = new ArrayCache();
        $this->region = new DefaultRegion('default.region.test', $this->cache);
    }

    public function testGetters()
    {
        $this->assertEquals('default.region.test', $this->region->getName());
        $this->assertSame($this->cache, $this->region->getCache());
    }

    static public function dataProviderCacheValues()
    {
        return array(
            array(new CacheKeyMock('key.1'), new CacheEntryMock(array('id'=>1, 'name' => 'bar'))),
            array(new CacheKeyMock('key.2'), new CacheEntryMock(array('id'=>2, 'name' => 'foo'))),
        );
    }

    /**
     * @dataProvider dataProviderCacheValues
     */
    public function testPutGetContainsEvict($key, $value)
    {
        $this->assertFalse($this->region->contains($key));

        $this->region->put($key, $value);

        $this->assertTrue($this->region->contains($key));

        $actual = $this->region->get($key);

        $this->assertEquals($value, $actual);
        
        $this->region->evict($key);

        $this->assertFalse($this->region->contains($key));
    }

    public function testEvictAll()
    {
        $key1 = new CacheKeyMock('key.1');
        $key2 = new CacheKeyMock('key.2');

        $this->assertFalse($this->region->contains($key1));
        $this->assertFalse($this->region->contains($key2));

        $this->region->put($key1, new CacheEntryMock(array('value' => 'foo')));
        $this->region->put($key2, new CacheEntryMock(array('value' => 'bar')));

        $this->assertTrue($this->region->contains($key1));
        $this->assertTrue($this->region->contains($key2));

        $this->region->evictAll();

        $this->assertFalse($this->region->contains($key1));
        $this->assertFalse($this->region->contains($key2));
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
}