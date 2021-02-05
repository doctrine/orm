<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Cache\Region;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-2183
 */
abstract class AbstractRegionTest extends OrmFunctionalTestCase
{
    /** @var Region */
    protected $region;

    /** @var ArrayCache */
    protected $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache  = new ArrayCache();
        $this->region = $this->createRegion();
    }

    abstract protected function createRegion(): Region;

    public static function dataProviderCacheValues()
    {
        return [
            [new CacheKeyMock('key.1'), new CacheEntryMock(['id' => 1, 'name' => 'bar'])],
            [new CacheKeyMock('key.2'), new CacheEntryMock(['id' => 2, 'name' => 'foo'])],
        ];
    }

    /**
     * @dataProvider dataProviderCacheValues
     */
    public function testPutGetContainsEvict($key, $value): void
    {
        $this->assertFalse($this->region->contains($key));

        $this->region->put($key, $value);

        $this->assertTrue($this->region->contains($key));

        $actual = $this->region->get($key);

        $this->assertEquals($value, $actual);

        $this->region->evict($key);

        $this->assertFalse($this->region->contains($key));
    }

    public function testEvictAll(): void
    {
        $key1 = new CacheKeyMock('key.1');
        $key2 = new CacheKeyMock('key.2');

        $this->assertFalse($this->region->contains($key1));
        $this->assertFalse($this->region->contains($key2));

        $this->region->put($key1, new CacheEntryMock(['value' => 'foo']));
        $this->region->put($key2, new CacheEntryMock(['value' => 'bar']));

        $this->assertTrue($this->region->contains($key1));
        $this->assertTrue($this->region->contains($key2));

        $this->region->evictAll();

        $this->assertFalse($this->region->contains($key1));
        $this->assertFalse($this->region->contains($key2));
    }
}
