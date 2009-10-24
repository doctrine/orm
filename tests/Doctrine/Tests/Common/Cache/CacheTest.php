<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ArrayCache;

require_once __DIR__ . '/../../TestInit.php';

class CacheTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testCount()
    {
        $cache = new ArrayCache();
        $cache->setManageCacheIds(true);
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $this->assertEquals($cache->count(), 2);
    }

    public function testDeleteAll()
    {
        $cache = new ArrayCache();
        $cache->setManageCacheIds(true);
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->deleteAll();

        $this->assertEquals($cache->count(), 0);
    }

    public function testDeleteByRegex()
    {
        $cache = new ArrayCache();
        $cache->setManageCacheIds(true);
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->deleteByRegex('/test_key[0-9]/');

        $this->assertEquals($cache->count(), 0);
    }

    public function testDeleteByPrefix()
    {
        $cache = new ArrayCache();
        $cache->setManageCacheIds(true);
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->deleteByPrefix('test_key');

        $this->assertEquals($cache->count(), 0);
    }

    public function testDeleteBySuffix()
    {
        $cache = new ArrayCache();
        $cache->setManageCacheIds(true);
        $cache->save('1test_key', '1');
        $cache->save('2test_key', '2');
        $cache->deleteBySuffix('test_key');

        $this->assertEquals($cache->count(), 0);
    }

    public function testDeleteByWildcard()
    {
        $cache = new ArrayCache();
        $cache->setManageCacheIds(true);
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->delete('test_key*');

        $this->assertEquals($cache->count(), 0);
    }

    public function testNamespace()
    {
        $cache = new ArrayCache();
        $cache->setManageCacheIds(true);
        $cache->setNamespace('test_');
        $cache->save('key1', 'test');
        $this->assertTrue($cache->contains('key1'));

        $ids = $cache->getIds();
        $this->assertTrue(in_array('test_key1', $ids));
    }
}