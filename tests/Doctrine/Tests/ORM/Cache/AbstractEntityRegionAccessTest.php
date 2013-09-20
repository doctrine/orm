<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\ORM\Cache\AbstractRegionAccessTest;

/**
 * @group DDC-2183
 */
abstract class AbstractEntityRegionAccessTest extends AbstractRegionAccessTest
{
    public function testAfterInsert()
    {
        $key1  = new CacheKeyMock('key.1');
        $key2  = new CacheKeyMock('key.2');

        $this->assertNull($this->regionAccess->get($key1));
        $this->assertNull($this->regionAccess->get($key2));

        $this->regionAccess->afterInsert($key1, new CacheEntryMock(array('value' => 'foo')));
        $this->regionAccess->afterInsert($key2, new CacheEntryMock(array('value' => 'bar')));

        $this->assertNotNull($this->regionAccess->get($key1));
        $this->assertNotNull($this->regionAccess->get($key2));
        
        $this->assertEquals(new CacheEntryMock(array('value' => 'foo')), $this->regionAccess->get($key1));
        $this->assertEquals(new CacheEntryMock(array('value' => 'bar')), $this->regionAccess->get($key2));
    }

    public function testAfterUpdate()
    {
        $key1  = new CacheKeyMock('key.1');
        $key2  = new CacheKeyMock('key.2');

        $this->assertNull($this->regionAccess->get($key1));
        $this->assertNull($this->regionAccess->get($key2));

        $this->regionAccess->afterUpdate($key1, new CacheEntryMock(array('value' => 'foo')));
        $this->regionAccess->afterUpdate($key2, new CacheEntryMock(array('value' => 'bar')));

        $this->assertEquals(new CacheEntryMock(array('value' => 'foo')), $this->regionAccess->get($key1));
        $this->assertEquals(new CacheEntryMock(array('value' => 'bar')), $this->regionAccess->get($key2));
    }
}
