<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\Tests\DoctrineTestCase;

/**
 * @group DDC-2183
 */
class CacheKeyTest extends DoctrineTestCase
{
    public function testEntityCacheKeyIdentifierCollision()
    {
        $key1 = new EntityCacheKey('Foo', ['id'=>1]);
        $key2 = new EntityCacheKey('Bar', ['id'=>1]);

        $this->assertNotEquals($key1->hash, $key2->hash);
    }

    public function testEntityCacheKeyIdentifierType()
    {
        $key1 = new EntityCacheKey('Foo', ['id'=>1]);
        $key2 = new EntityCacheKey('Foo', ['id'=>'1']);

        $this->assertEquals($key1->hash, $key2->hash);
    }

    public function testEntityCacheKeyIdentifierOrder()
    {
        $key1 = new EntityCacheKey('Foo', ['foo_bar'=>1, 'bar_foo'=> 2]);
        $key2 = new EntityCacheKey('Foo', ['bar_foo'=>2, 'foo_bar'=> 1]);

        $this->assertEquals($key1->hash, $key2->hash);
    }

    public function testCollectionCacheKeyIdentifierType()
    {
        $key1 = new CollectionCacheKey('Foo', 'assoc', ['id'=>1]);
        $key2 = new CollectionCacheKey('Foo', 'assoc', ['id'=>'1']);

        $this->assertEquals($key1->hash, $key2->hash);
    }

    public function testCollectionCacheKeyIdentifierOrder()
    {
        $key1 = new CollectionCacheKey('Foo', 'assoc', ['foo_bar'=>1, 'bar_foo'=> 2]);
        $key2 = new CollectionCacheKey('Foo', 'assoc', ['bar_foo'=>2, 'foo_bar'=> 1]);

        $this->assertEquals($key1->hash, $key2->hash);
    }

    public function testCollectionCacheKeyIdentifierCollision()
    {
        $key1 = new CollectionCacheKey('Foo', 'assoc', ['id'=>1]);
        $key2 = new CollectionCacheKey('Bar', 'assoc', ['id'=>1]);

        $this->assertNotEquals($key1->hash, $key2->hash);
    }

    public function testCollectionCacheKeyAssociationCollision()
    {
        $key1 = new CollectionCacheKey('Foo', 'assoc1', ['id'=>1]);
        $key2 = new CollectionCacheKey('Foo', 'assoc2', ['id'=>1]);

        $this->assertNotEquals($key1->hash, $key2->hash);
    }
}
