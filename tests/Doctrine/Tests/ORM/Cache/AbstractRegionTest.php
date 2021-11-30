<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\Cache\Region;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @group DDC-2183
 */
abstract class AbstractRegionTest extends OrmFunctionalTestCase
{
    /** @var Region */
    protected $region;

    /** @var Cache */
    protected $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache  = DoctrineProvider::wrap(new ArrayAdapter());
        $this->region = $this->createRegion();
    }

    abstract protected function createRegion(): Region;

    /** @psalm-return list<array{CacheKeyMock, CacheEntryMock}> */
    public static function dataProviderCacheValues(): array
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
        self::assertFalse($this->region->contains($key));

        $this->region->put($key, $value);

        self::assertTrue($this->region->contains($key));

        $actual = $this->region->get($key);

        self::assertEquals($value, $actual);

        $this->region->evict($key);

        self::assertFalse($this->region->contains($key));
    }

    public function testEvictAll(): void
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
