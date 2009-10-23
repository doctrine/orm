<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ArrayCache;

require_once __DIR__ . '/../../TestInit.php';

class ArrayCacheTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testArrayCacheDriver()
    {
        $cache = new ArrayCache();
        $cache->setManageCacheKeys(true);

        // Test save
        $cache->save('test_key', 'testing this out');

        // Test contains to test that save() worked
        $this->assertTrue($cache->contains('test_key'));

        // Test fetch
        $this->assertEquals('testing this out', $cache->fetch('test_key'));

        // Test count
        $this->assertEquals(1, $cache->count());

        // Test delete
        $cache->save('test_key2', 'test2');
        $cache->delete('test_key2');
        $this->assertFalse($cache->contains('test_key2'));

        // Test delete all
        $cache->deleteAll();
        $this->assertEquals(0, $cache->count());
    }
}