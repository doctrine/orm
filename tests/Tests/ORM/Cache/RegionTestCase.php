<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\Region;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/** @template TRegion of Region */
#[Group('DDC-2183')]
abstract class RegionTestCase extends OrmFunctionalTestCase
{
    /** @phpstan-var TRegion */
    protected Region $region;
    protected CacheItemPoolInterface $cacheItemPool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheItemPool = new ArrayAdapter();
        $this->region        = $this->createRegion();
    }

    /** @phpstan-return TRegion */
    abstract protected function createRegion(): Region;

    /** @phpstan-return list<array{CacheKeyMock, CacheEntryMock}> */
    public static function dataProviderCacheValues(): array
    {
        return [
            [new CacheKeyMock('key.1'), new CacheEntryMock(['id' => 1, 'name' => 'bar'])],
            [new CacheKeyMock('key.2'), new CacheEntryMock(['id' => 2, 'name' => 'foo'])],
        ];
    }

    #[DataProvider('dataProviderCacheValues')]
    public function testPutGetContainsEvict(CacheKey $key, CacheEntry $value): void
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
