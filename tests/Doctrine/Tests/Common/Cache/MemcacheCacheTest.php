<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\MemcacheCache;

require_once __DIR__ . '/../../TestInit.php';

class MemcacheCacheTest extends \Doctrine\Tests\DoctrineTestCase
{
    private $_memcache;
    
    public function setUp()
    {
        if (extension_loaded('memcache')) {
            $this->_memcache = new \Memcache;
            $ok = @$this->_memcache->connect('localhost', 11211);
            if (!$ok) {
                $this->markTestSkipped('The ' . __CLASS__ .' requires the use of memcache');
            }
        } else {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of memcache');
        }
    }

    public function testMemcacheCacheDriver()
    {
        $cache = new MemcacheCache();
        $cache->setMemcache($this->_memcache);

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