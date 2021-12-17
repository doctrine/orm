<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\Tests\DoctrineTestCase;

/**
 * @group DDC-2183
 */
class CacheKeyTest extends DoctrineTestCase
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
}
