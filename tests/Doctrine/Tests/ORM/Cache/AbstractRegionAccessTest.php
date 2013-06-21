<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\ORM\Cache\Region\DefaultRegion;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-2183
 */
abstract class AbstractRegionAccessTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $cache;

    /**
     * @var \Doctrine\ORM\Cache\Region
     */
    protected $region;

    /**
     * @var \Doctrine\ORM\Cache\RegionAccess
     */
    protected $regionAccess;

    protected function setUp()
    {
        parent::setUp();

        $this->cache        = $this->createCache();
        $this->region       = $this->createRegion($this->cache);
        $this->regionAccess = $this->createRegionAccess($this->region);
    }

    /**
     * @return \Doctrine\ORM\Cache\RegionAccess
     */
    abstract protected function createRegionAccess(Region $region);

    /**
     * @param \Doctrine\Common\Cache\Cache $cache
     * 
     * @return \Doctrine\ORM\Cache\Region
     */
    protected function createRegion(Cache $cache)
    {
        $name = strtolower(str_replace('\\', '.', get_called_class()));
        
        return new DefaultRegion($name, $cache);
    }

    /**
     * @return \Doctrine\Common\Cache\Cache
     */
    protected function createCache()
    {
        return new ArrayCache();
    }

    public function testGetRegion()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache\Region', $this->regionAccess->getRegion());
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
    public function testPutGetAndEvict($key, $entry)
    {
        $this->assertNull($this->regionAccess->get($key));

        $this->regionAccess->put($key, $entry);

        $this->assertEquals($entry, $this->regionAccess->get($key));

        $this->regionAccess->evict($key);

        $this->assertNull($this->regionAccess->get($key));
    }

    public function testEvictAll()
    {
        $key1  = new CacheKeyMock('key.1');
        $key2  = new CacheKeyMock('key.2');

        $this->assertNull($this->regionAccess->get($key1));
        $this->assertNull($this->regionAccess->get($key2));

        $this->regionAccess->put($key1, new CacheEntryMock(array('value' => 'foo')));
        $this->regionAccess->put($key2, new CacheEntryMock(array('value' => 'bar')));

        $this->assertNotNull($this->regionAccess->get($key1));
        $this->assertNotNull($this->regionAccess->get($key2));

        $this->assertEquals(new CacheEntryMock(array('value' => 'foo')), $this->regionAccess->get($key1));
        $this->assertEquals(new CacheEntryMock(array('value' => 'bar')), $this->regionAccess->get($key2));

        $this->regionAccess->evictAll();

        $this->assertNull($this->regionAccess->get($key1));
        $this->assertNull($this->regionAccess->get($key2));
    }

    public function testAfterInsert()
    {
        $key1  = new CacheKeyMock('key.1');
        $key2  = new CacheKeyMock('key.2');

        $this->assertNull($this->regionAccess->get($key1));
        $this->assertNull($this->regionAccess->get($key2));

        $this->regionAccess->afterInsert($key1, new CacheEntryMock(array('value' => 'foo')));
        $this->regionAccess->afterInsert($key2, new CacheEntryMock(array('value' => 'bar')));

        $this->assertNotNull($this->regionAccess->get($key1));
        $this->assertNotNull($this->regionAccess->get($key2));
        
        $this->assertEquals(new CacheEntryMock(array('value' => 'foo')), $this->regionAccess->get($key1));
        $this->assertEquals(new CacheEntryMock(array('value' => 'bar')), $this->regionAccess->get($key2));
    }

    public function testAfterUpdate()
    {
        $key1  = new CacheKeyMock('key.1');
        $key2  = new CacheKeyMock('key.2');

        $this->assertNull($this->regionAccess->get($key1));
        $this->assertNull($this->regionAccess->get($key2));

        $this->regionAccess->afterUpdate($key1, new CacheEntryMock(array('value' => 'foo')));
        $this->regionAccess->afterUpdate($key2, new CacheEntryMock(array('value' => 'bar')));

        $this->assertEquals(new CacheEntryMock(array('value' => 'foo')), $this->regionAccess->get($key1));
        $this->assertEquals(new CacheEntryMock(array('value' => 'bar')), $this->regionAccess->get($key2));
    }
}
