<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\EntityCacheKey;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Stringable;

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

    /**
     * @param array<string, string> $firstId
     * @param array<string, string> $secondId
     */
    #[DataProvider('collidingIdentifiers')]
    public function testEntityIdentifiersDoNotCollide(array $firstId, array $secondId): void
    {
        self::assertNotSame(
            (new EntityCacheKey('Foo', $firstId))->hash,
            (new EntityCacheKey('Foo', $secondId))->hash,
        );
    }

    /**
     * @param array<string, string> $firstId
     * @param array<string, string> $secondId
     */
    #[DataProvider('collidingIdentifiers')]
    public function testCollectionOwnerIdentifiersDoNotCollide(array $firstId, array $secondId): void
    {
        self::assertNotSame(
            (new CollectionCacheKey('Foo', 'items', $firstId))->hash,
            (new CollectionCacheKey('Foo', 'items', $secondId))->hash,
        );
    }

    /** @return iterable<string, array{array<string, string>, array<string, string>}> */
    public static function collidingIdentifiers(): iterable
    {
        yield 'spaces between components' => [['a' => 'a b', 'b' => 'c'], ['a' => 'a', 'b' => 'b c']];
        yield 'empty components' => [['a' => '', 'b' => ' x'], ['a' => ' ', 'b' => 'x']];
        yield 'backslash and dot' => [['id' => 'a\\b'], ['id' => 'a.b']];
    }

    public function testAssociationAndFilterHashDoNotCollide(): void
    {
        self::assertNotSame(
            (new CollectionCacheKey('Foo', 'items_filter', ['id' => 1]))->hash,
            (new CollectionCacheKey('Foo', 'items', ['id' => 1], 'filter'))->hash,
        );
    }

    public function testOwnerIdentifierAndAssociationDoNotCollide(): void
    {
        self::assertNotSame(
            (new CollectionCacheKey('Foo', 'items', ['id' => '1__other']))->hash,
            (new CollectionCacheKey('Foo', 'other__items', ['id' => '1']))->hash,
        );
    }

    public function testStringableIdentifiersAreNormalizedToStrings(): void
    {
        $identifier = new class implements Stringable {
            public function __toString(): string
            {
                return '1';
            }
        };

        self::assertSame(
            (new EntityCacheKey('Foo', ['id' => '1']))->hash,
            (new EntityCacheKey('Foo', ['id' => $identifier]))->hash,
        );
        self::assertSame(
            (new CollectionCacheKey('Foo', 'items', ['id' => '1']))->hash,
            (new CollectionCacheKey('Foo', 'items', ['id' => $identifier]))->hash,
        );
    }

    public function testEntityClassNamesAreCaseInsensitive(): void
    {
        self::assertSame(
            (new EntityCacheKey('Foo', ['id' => 1]))->hash,
            (new EntityCacheKey('FOO', ['id' => 1]))->hash,
        );
        self::assertSame(
            (new CollectionCacheKey('Foo', 'items', ['id' => 1]))->hash,
            (new CollectionCacheKey('FOO', 'items', ['id' => 1]))->hash,
        );
    }

    public function testConstructor(): void
    {
        $key = new class ('my-hash') extends CacheKey {
        };

        self::assertSame('my-hash', $key->hash);
    }
}
