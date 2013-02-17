<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Cache\Access\NonStrictReadWriteRegionAccessStrategy;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-2183
 */
class NonStrictReadWriteRegionAccessTest extends \Doctrine\Tests\OrmTestCase
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

        $this->cache        = new ArrayCache();
        $this->region       = $this->createRegion();
        $this->regionAccess = $this->createRegionAccess();
    }

    protected function createRegionAccess()
    {
        return new NonStrictReadWriteRegionAccessStrategy($this->region);
    }

    protected function createRegion()
    {
        $name = strtolower(str_replace('\\', '.', get_called_class()));
        
        return new DefaultRegion($name, $this->cache);
    }

    public function testGetRegion()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache\Region', $this->regionAccess->getRegion());
    }

    static public function dataProviderCacheValues()
    {
        $entityName = '\Doctrine\Tests\Models\Cache\Country';
        
        return array(
            array(new EntityCacheKey($entityName, array('id'=>1)), array('id'=>1, 'name' => 'bar')),
            array(new EntityCacheKey($entityName, array('id'=>2)), array('id'=>2, 'name' => 'foo')),
        );
    }

    /**
     * @dataProvider dataProviderCacheValues
     */
    public function testPutGetAndEvict($key, $value)
    {
        $this->assertNull($this->regionAccess->get($key));

        $this->regionAccess->put($key, $value);

        $this->assertEquals($value, $this->regionAccess->get($key));

        $this->regionAccess->evict($key);

        $this->assertNull($this->regionAccess->get($key));
    }

    public function testEvictAll()
    {
        $key1 = new DefaultRegionTestKey('key.1');
        $key2 = new DefaultRegionTestKey('key.2');

        $this->assertNull($this->regionAccess->get($key1));
        $this->assertNull($this->regionAccess->get($key2));

        $this->regionAccess->put($key1, array('value' => 'foo'));
        $this->regionAccess->put($key2, array('value' => 'bar'));

        $this->assertEquals(array('value' => 'foo'), $this->regionAccess->get($key1));
        $this->assertEquals(array('value' => 'bar'), $this->regionAccess->get($key2));

        $this->regionAccess->evictAll();

        $this->assertNull($this->regionAccess->get($key1));
        $this->assertNull($this->regionAccess->get($key2));
    }

    public function testAfterInsert()
    {
        $key1 = new DefaultRegionTestKey('key.1');
        $key2 = new DefaultRegionTestKey('key.2');

        $this->assertNull($this->regionAccess->get($key1));
        $this->assertNull($this->regionAccess->get($key2));

        $this->regionAccess->afterInsert($key1, array('value' => 'foo'));
        $this->regionAccess->afterInsert($key2, array('value' => 'bar'));

        $this->assertEquals(array('value' => 'foo'), $this->regionAccess->get($key1));
        $this->assertEquals(array('value' => 'bar'), $this->regionAccess->get($key2));
    }

    public function testAfterUpdate()
    {
        $key1 = new DefaultRegionTestKey('key.1');
        $key2 = new DefaultRegionTestKey('key.2');

        $this->assertNull($this->regionAccess->get($key1));
        $this->assertNull($this->regionAccess->get($key2));

        $this->regionAccess->afterUpdate($key1, array('value' => 'foo'));
        $this->regionAccess->afterUpdate($key2, array('value' => 'bar'));

        $this->assertEquals(array('value' => 'foo'), $this->regionAccess->get($key1));
        $this->assertEquals(array('value' => 'bar'), $this->regionAccess->get($key2));
    }
}
