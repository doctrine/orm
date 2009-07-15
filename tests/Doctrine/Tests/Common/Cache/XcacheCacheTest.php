<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\XcacheCache;

require_once __DIR__ . '/../../TestInit.php';

class XcacheCacheTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function setUp()
    {
        if ( ! extension_loaded('xcache')) {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of xcache');
        }
    }

    public function testXcacheCacheDriver()
    {
        $cache = new XcacheCache();

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
}