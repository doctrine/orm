<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region;
use Doctrine\Common\Cache\Cache;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\ConcurrentRegionMock;
use Doctrine\ORM\Cache\Access\ConcurrentRegionAccessStrategy;

/**
 * @group DDC-2183
 */
class ConcurrentRegionAccessTest extends AbstractRegionAccessTest
{

    /**
     * @var Doctrine\Tests\Mocks\ConcurrentRegionMock
     */
    protected $regionAccess;

    protected function createRegionAccess(Region $region)
    {
        return new ConcurrentRegionAccessStrategy($region);
    }

    protected function createRegion(Cache $cache)
    {
        return new ConcurrentRegionMock(parent::createRegion($cache));
    }

    public function testLockItemAndUnlockItem()
    {
        $key1   = new CacheKeyMock('key.1');
        $key2   = new CacheKeyMock('key.2');
        $entry1 = new CacheEntryMock(array('value' => 'foo'));
        $entry2 = new CacheEntryMock(array('value' => 'bar'));

        $this->regionAccess->put($key1, $entry1);
        $this->regionAccess->put($key2, $entry2);

        $this->assertNotNull($this->regionAccess->get($key1));
        $this->assertNotNull($this->regionAccess->get($key2));

        $lock1 = $this->regionAccess->lockItem($key1);
        $lock2 = $this->regionAccess->lockItem($key1);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Lock', $lock1);
        $this->assertNull($lock2);

        $this->assertNull($this->regionAccess->get($key1));
        $this->assertNotNull($this->regionAccess->get($key2));

        $this->regionAccess->unlockItem($key1, $lock1);

        $this->assertNotNull($this->regionAccess->get($key1));
        $this->assertNotNull($this->regionAccess->get($key2));

        $this->assertEquals($entry1, $this->regionAccess->get($key1));
        $this->assertEquals($entry2, $this->regionAccess->get($key2));
    }

    public function testLockWriteOnUpdate()
    {
        $key    = new CacheKeyMock('key.1');
        $entry  = new CacheEntryMock(array('value' => 'foo'));
        $entry1 = new CacheEntryMock(array('value' => 'foo'));
        $region = $this->regionAccess->getRegion();

        $this->assertTrue($this->regionAccess->afterInsert($key, $entry));
        $this->assertEquals($entry, $this->regionAccess->get($key));

        $lock = $this->regionAccess->lockItem($key);
        $this->assertNull($this->regionAccess->get($key));
        $this->assertInstanceOf('Doctrine\ORM\Cache\Lock', $lock);

         //Somehow another proc get the lock
        $region->setLock($key, Lock::createLockRead());

        $this->assertFalse($this->regionAccess->afterUpdate($key, $entry1, $lock));
    }

    public function testExceptionShouldUnlockItem()
    {
        $key    = new CacheKeyMock('key.1');
        $entry  = new CacheEntryMock(array('value' => 'foo'));
        $region = $this->regionAccess->getRegion();

        $this->assertTrue($this->regionAccess->afterInsert($key, $entry));
        $this->assertEquals($entry, $this->regionAccess->get($key));

        $lock = $this->regionAccess->lockItem($key);
        $this->assertNull($this->regionAccess->get($key));
        $this->assertInstanceOf('Doctrine\ORM\Cache\Lock', $lock);

        $region->addException('put', new \RuntimeException('Some concurrency exception'));

        try {
            $this->regionAccess->afterUpdate($key, $entry, $lock);

            $this->fail('Expected Exception');

        } catch (\Doctrine\ORM\Cache\CacheException $exc) {
            $this->assertEquals('Some concurrency exception', $exc->getMessage());
        }

        $this->assertNotNull($this->regionAccess->get($key));
        $this->assertEquals($entry, $this->regionAccess->get($key));
    }
}