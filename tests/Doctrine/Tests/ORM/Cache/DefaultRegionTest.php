<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CacheKey;

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
            array(new DefaultRegionTestKey('key.1'), new DefaultRegionTestEntry(array('id'=>1, 'name' => 'bar'))),
            array(new DefaultRegionTestKey('key.2'), new DefaultRegionTestEntry(array('id'=>2, 'name' => 'foo'))),
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
        $key1 = new DefaultRegionTestKey('key.1');
        $key2 = new DefaultRegionTestKey('key.2');

        $this->assertFalse($this->region->contains($key1));
        $this->assertFalse($this->region->contains($key2));

        $this->region->put($key1, new DefaultRegionTestEntry(array('value' => 'foo')));
        $this->region->put($key2, new DefaultRegionTestEntry(array('value' => 'bar')));

        $this->assertTrue($this->region->contains($key1));
        $this->assertTrue($this->region->contains($key2));

        $this->region->evictAll();

        $this->assertFalse($this->region->contains($key1));
        $this->assertFalse($this->region->contains($key2));
    }
}

class DefaultRegionTestKey implements CacheKey
{

    function __construct($hash)
    {
        $this->hash = $hash;
    }

    public function hash()
    {
        return $this->hash;
    }
}

class DefaultRegionTestEntry extends \ArrayObject implements CacheEntry
{

}