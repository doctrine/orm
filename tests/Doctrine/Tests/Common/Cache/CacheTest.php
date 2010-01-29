<?php

namespace Doctrine\Tests\Common\Cache;

require_once __DIR__ . '/../../TestInit.php';

abstract class CacheTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testBasics()
    {
        $cache = $this->_getCacheDriver();

        // Test save
        $cache->save('test_key', 'testing this out');

        // Test contains to test that save() worked
        $this->assertTrue($cache->contains('test_key'));

        // Test fetch
        $this->assertEquals('testing this out', $cache->fetch('test_key'));

        // Test delete
        $cache->save('test_key2', 'test2');
        $cache->delete('test_key2');
        $this->assertFalse($cache->contains('test_key2'));
    }

    public function testDeleteAll()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->deleteAll();

        $this->assertFalse($cache->contains('test_key1'));
        $this->assertFalse($cache->contains('test_key2'));
    }

    public function testDeleteByRegex()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->deleteByRegex('/test_key[0-9]/');

        $this->assertFalse($cache->contains('test_key1'));
        $this->assertFalse($cache->contains('test_key2'));
    }

    public function testDeleteByPrefix()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->deleteByPrefix('test_key');

        $this->assertFalse($cache->contains('test_key1'));
        $this->assertFalse($cache->contains('test_key2'));
    }

    public function testDeleteBySuffix()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('1test_key', '1');
        $cache->save('2test_key', '2');
        $cache->deleteBySuffix('test_key');

        $this->assertFalse($cache->contains('1test_key'));
        $this->assertFalse($cache->contains('2test_key'));
    }

    public function testDeleteByWildcard()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->delete('test_key*');

        $this->assertFalse($cache->contains('test_key1'));
        $this->assertFalse($cache->contains('test_key2'));
    }

    public function testNamespace()
    {
        $cache = $this->_getCacheDriver();
        $cache->setNamespace('test_');
        $cache->save('key1', 'test');
        $this->assertTrue($cache->contains('key1'));

        $ids = $cache->getIds();
        $this->assertTrue(in_array('test_key1', $ids));
    }

    abstract protected function _getCacheDriver();
}