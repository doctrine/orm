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
        return [
            [new CacheKeyMock('key.1'), new CacheEntryMock(['id'=>1, 'name' => 'bar'])],
            [new CacheKeyMock('key.2'), new CacheEntryMock(['id'=>2, 'name' => 'foo'])],
        ];
    }

    /**
     * @dataProvider dataProviderCacheValues
     */
    public function testPutGetContainsEvict($key, $value)
    {
        self::assertFalse($this->region->contains($key));

        $this->region->put($key, $value);

        self::assertTrue($this->region->contains($key));

        $actual = $this->region->get($key);

        self::assertEquals($value, $actual);

        $this->region->evict($key);

        self::assertFalse($this->region->contains($key));
    }

    public function testEvictAll()
    {
        $key1 = new CacheKeyMock('key.1');
        $key2 = new CacheKeyMock('key.2');

        self::assertFalse($this->region->contains($key1));
        self::assertFalse($this->region->contains($key2));

        $this->region->put($key1, new CacheEntryMock(['value' => 'foo']));
        $this->region->put($key2, new CacheEntryMock(['value' => 'bar']));

        self::assertTrue($this->region->contains($key1));
        self::assertTrue($this->region->contains($key2));

        $this->region->evictAll();

        self::assertFalse($this->region->contains($key1));
        self::assertFalse($this->region->contains($key2));
    }
}
