<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\EntityCacheKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('DDC-2183')]
class CacheKeyTest extends TestCase
{
    public function testEntityCacheKeyIdentifierCollision(): void
    {
        $key1 = new EntityCacheKey('Foo', ['id' => 1]);
        $key2 = new EntityCacheKey('Bar', ['id' => 1]);

        self::assertNotEquals($key1->hash, $key2->hash);
    }

    public function testEntityCacheKeyIdentifierType(): void
    {
        $key1 = new EntityCacheKey('Foo', ['id' => 1]);
        $key2 = new EntityCacheKey('Foo', ['id' => '1']);

        self::assertEquals($key1->hash, $key2->hash);
    }

    public function testEntityCacheKeyIdentifierOrder(): void
    {
        $key1 = new EntityCacheKey('Foo', ['foo_bar' => 1, 'bar_foo' => 2]);
        $key2 = new EntityCacheKey('Foo', ['bar_foo' => 2, 'foo_bar' => 1]);

        self::assertEquals($key1->hash, $key2->hash);
    }

    public function testCollectionCacheKeyIdentifierType(): void
    {
        $key1 = new CollectionCacheKey('Foo', 'assoc', ['id' => 1]);
        $key2 = new CollectionCacheKey('Foo', 'assoc', ['id' => '1']);

        self::assertEquals($key1->hash, $key2->hash);
    }

    public function testCollectionCacheKeyIdentifierOrder(): void
    {
        $key1 = new CollectionCacheKey('Foo', 'assoc', ['foo_bar' => 1, 'bar_foo' => 2]);
        $key2 = new CollectionCacheKey('Foo', 'assoc', ['bar_foo' => 2, 'foo_bar' => 1]);

        self::assertEquals($key1->hash, $key2->hash);
    }

    public function testCollectionCacheKeyIdentifierCollision(): void
    {
        $key1 = new CollectionCacheKey('Foo', 'assoc', ['id' => 1]);
        $key2 = new CollectionCacheKey('Bar', 'assoc', ['id' => 1]);

        self::assertNotEquals($key1->hash, $key2->hash);
    }

    public function testCollectionCacheKeyAssociationCollision(): void
    {
        $key1 = new CollectionCacheKey('Foo', 'assoc1', ['id' => 1]);
        $key2 = new CollectionCacheKey('Foo', 'assoc2', ['id' => 1]);

        self::assertNotEquals($key1->hash, $key2->hash);
    }

    public function testConstructor(): void
    {
        $key = new class ('my-hash') extends CacheKey {
        };

        self::assertSame('my-hash', $key->hash);
    }

    public function testCollectionVersionAndFilterAreSeparateKeyComponents(): void
    {
        $key = new CollectionCacheKey('Foo', 'items', ['id' => 1], 'filter', 'first');
        self::assertSame('filter', $key->filterHash);
        self::assertNotSame($key->hash, (new CollectionCacheKey('Foo', 'items', ['id' => 1], 'filter', 'second'))->hash);
        self::assertNotSame($key->hash, (new CollectionCacheKey('Foo', 'items', ['id' => 1], 'other-filter', 'first'))->hash);
        self::assertNotSame($key->hash, (new CollectionCacheKey('Foo', 'items', ['id' => 1], 'filter'))->hash);
    }
}
