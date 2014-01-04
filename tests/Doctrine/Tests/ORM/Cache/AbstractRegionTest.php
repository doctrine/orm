<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\Common\Cache\ArrayCache;

/**
 * @group DDC-2183
 */
abstract class AbstractRegionTest extends OrmFunctionalTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\Region
     */
    protected $region;

    /**
     * @var \Doctrine\Common\Cache\ArrayCache
     */
    protected $cache;

    protected function setUp()
    {
        parent::setUp();

        $this->cache  = new ArrayCache();
        $this->region = $this->createRegion();
    }

    /**
     * @return \Doctrine\ORM\Cache\Region
     */
    protected abstract function createRegion();

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
}