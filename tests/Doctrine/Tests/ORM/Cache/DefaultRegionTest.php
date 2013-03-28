<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Common\Cache\ArrayCache;

/**
 * @group DDC-2183
 */
class DefaultRegionTest extends \Doctrine\Tests\OrmFunctionalTestCase
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
            array(new DefaultRegionTestKey('key.1'), array('id'=>1, 'name' => 'bar')),
            array(new DefaultRegionTestKey('key.2'), array('id'=>2, 'name' => 'foo')),
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

    public function testCount()
    {
        $key1 = new DefaultRegionTestKey('key.1');
        $key2 = new DefaultRegionTestKey('key.2');
        $key3 = new DefaultRegionTestKey('key.3');

        $this->assertCount(0, $this->region);
        $this->assertInstanceOf('Countable', $this->region);

        $this->region->put($key1, array('value' => 'foo'));
        $this->assertCount(1, $this->region);

        $this->region->put($key2, array('value' => 'bar'));
        $this->assertCount(2, $this->region);

        $this->region->put($key2, array('value' => 'bar1'));
        $this->assertCount(2, $this->region);

        $this->region->put($key3, array('value' => 'baz'));
        $this->assertCount(3, $this->region);
    }

    public function testEvictAll()
    {
        $key1 = new DefaultRegionTestKey('key.1');
        $key2 = new DefaultRegionTestKey('key.2');

        $this->assertCount(0, $this->region);
        $this->assertFalse($this->region->contains($key1));
        $this->assertFalse($this->region->contains($key2));

        $this->region->put($key1, array('value' => 'foo'));
        $this->region->put($key2, array('value' => 'bar'));

        $this->assertTrue($this->region->contains($key1));
        $this->assertTrue($this->region->contains($key2));
        $this->assertCount(2, $this->region);

        $this->region->evictAll();

        $this->assertCount(0, $this->region);
        $this->assertFalse($this->region->contains($key1));
        $this->assertFalse($this->region->contains($key2));
    }

    public function testToArray()
    {
        $key1 = new DefaultRegionTestKey('key.1');
        $key2 = new DefaultRegionTestKey('key.2');

        $this->assertCount(0, $this->region);
        $this->assertEquals(array(), $this->region->toArray());

        $this->region->put($key1, array('value' => 'foo'));
        $this->region->put($key2, array('value' => 'bar'));

        $array = $this->region->toArray();

        $this->assertCount(2, $array);

        $this->assertArrayHasKey('value', $array[0]);
        $this->assertArrayHasKey('value', $array[1]);

        $this->assertEquals('foo', $array[0]['value']);
        $this->assertEquals('bar', $array[1]['value']);
    }
}

class DefaultRegionTestKey implements \Doctrine\ORM\Cache\CacheKey
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

