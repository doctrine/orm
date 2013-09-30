<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Cache\Region\FileLockRegion;
use Doctrine\ORM\Cache\ConcurrentRegion;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\Lock;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * @group DDC-2183
 */
class FileLockRegionTest extends AbstractRegionTest
{
    /**
     * @var \Doctrine\ORM\Cache\ConcurrentRegion
     */
    protected $region;
    
    /**
     * @var string
     */
    protected $directory;

    public function tearDown()
    {
        if ( ! is_dir($this->directory)) {
            return;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isFile() 
                ? @unlink($file->getRealPath())
                : @rmdir($file->getRealPath());
        }
    }

    /**
     * @param \Doctrine\ORM\Cache\ConcurrentRegion $region
     * @param \Doctrine\ORM\Cache\CacheKey $key
     *
     * @return string
     */
    private function getFileName(ConcurrentRegion $region, CacheKey $key)
    {
        $reflection = new \ReflectionMethod($region, 'getLockFileName');

        $reflection->setAccessible(true);

        return $reflection->invoke($region, $key);
    }

    protected function createRegion()
    {
        $this->directory = sys_get_temp_dir() . '/doctrine_lock_'. uniqid();

        $region = new DefaultRegion('concurren_region_test', $this->cache);

        return new FileLockRegion($region, $this->directory, 60);
    }

    public function testGetRegionName()
    {
        $this->assertEquals('concurren_region_test', $this->region->getName());
    }

    public function testLockAndUnlock()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(array('foo' => 'bar'));
        $file   = $this->getFileName($this->region, $key);

        $this->assertFalse($this->region->contains($key));
        $this->assertTrue($this->region->put($key, $entry));
        $this->assertTrue($this->region->contains($key));

        $lock = $this->region->lock($key);

        $this->assertFileExists($file);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Lock', $lock);
        $this->assertEquals($lock->value, file_get_contents($file));

        // should be not available after lock
        $this->assertFalse($this->region->contains($key));
        $this->assertNull($this->region->get($key));

        $this->assertTrue($this->region->unlock($key, $lock));
        $this->assertFileNotExists($file);
    }

    public function testLockWithExistingLock()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(array('foo' => 'bar'));
        $file   = $this->getFileName($this->region, $key);

        $this->assertFalse($this->region->contains($key));
        $this->assertTrue($this->region->put($key, $entry));
        $this->assertTrue($this->region->contains($key));

        file_put_contents($file, 'foo');
        $this->assertFileExists($file);
        $this->assertEquals('foo' , file_get_contents($file));

        $this->assertNull($this->region->lock($key));
        $this->assertEquals('foo' , file_get_contents($file));
        $this->assertFileExists($file);

        // should be not available
        $this->assertFalse($this->region->contains($key));
        $this->assertNull($this->region->get($key));
    }

    public function testUnlockWithExistingLock()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(array('foo' => 'bar'));
        $file   = $this->getFileName($this->region, $key);

        $this->assertFalse($this->region->contains($key));
        $this->assertTrue($this->region->put($key, $entry));
        $this->assertTrue($this->region->contains($key));

        $this->assertInstanceOf('Doctrine\ORM\Cache\Lock', $lock = $this->region->lock($key));
        $this->assertEquals($lock->value, file_get_contents($file));
        $this->assertFileExists($file);

        // change the lock
        file_put_contents($file, 'foo');
        $this->assertFileExists($file);
        $this->assertEquals('foo' , file_get_contents($file));

        //try to unlock
        $this->assertFalse($this->region->unlock($key, $lock));
        $this->assertEquals('foo' , file_get_contents($file));
        $this->assertFileExists($file);

        // should be not available
        $this->assertFalse($this->region->contains($key));
        $this->assertNull($this->region->get($key));
    }

    public function testPutWithExistingLock()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(array('foo' => 'bar'));
        $file   = $this->getFileName($this->region, $key);

        $this->assertFalse($this->region->contains($key));
        $this->assertTrue($this->region->put($key, $entry));
        $this->assertTrue($this->region->contains($key));

        // create lock
        file_put_contents($file, 'foo');
        $this->assertFileExists($file);
        $this->assertEquals('foo' , file_get_contents($file));

        $this->assertFalse($this->region->contains($key));
        $this->assertFalse($this->region->put($key, $entry));
        $this->assertFalse($this->region->contains($key));

        $this->assertFileExists($file);
        $this->assertEquals('foo' , file_get_contents($file));
    }

    public function testLockedEvict()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(array('foo' => 'bar'));
        $file   = $this->getFileName($this->region, $key);

        $this->assertFalse($this->region->contains($key));
        $this->assertTrue($this->region->put($key, $entry));
        $this->assertTrue($this->region->contains($key));

        $this->assertInstanceOf('Doctrine\ORM\Cache\Lock', $lock = $this->region->lock($key));
        $this->assertEquals($lock->value, file_get_contents($file));
        $this->assertFileExists($file);

        $this->assertFalse($this->region->contains($key));
        $this->assertTrue($this->region->evict($key));
        $this->assertFalse($this->region->contains($key));
        $this->assertFileNotExists($file);
    }

    public function testLockedEvictAll()
    {
        $key1    = new CacheKeyMock('key1');
        $entry1  = new CacheEntryMock(array('foo1' => 'bar1'));
        $file1   = $this->getFileName($this->region, $key1);

        $key2    = new CacheKeyMock('key2');
        $entry2  = new CacheEntryMock(array('foo2' => 'bar2'));
        $file2   = $this->getFileName($this->region, $key2);

        $this->assertFalse($this->region->contains($key1));
        $this->assertTrue($this->region->put($key1, $entry1));
        $this->assertTrue($this->region->contains($key1));

        $this->assertFalse($this->region->contains($key2));
        $this->assertTrue($this->region->put($key2, $entry2));
        $this->assertTrue($this->region->contains($key2));

        $this->assertInstanceOf('Doctrine\ORM\Cache\Lock', $lock1 = $this->region->lock($key1));
        $this->assertInstanceOf('Doctrine\ORM\Cache\Lock', $lock2 = $this->region->lock($key2));

        $this->assertEquals($lock2->value, file_get_contents($file2));
        $this->assertEquals($lock1->value, file_get_contents($file1));

        $this->assertFileExists($file1);
        $this->assertFileExists($file2);

        $this->assertTrue($this->region->evictAll());

        $this->assertFileNotExists($file1);
        $this->assertFileNotExists($file2);

        $this->assertFalse($this->region->contains($key1));
        $this->assertFalse($this->region->contains($key2));
    }

    public function testLockLifetime()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(array('foo' => 'bar'));
        $file   = $this->getFileName($this->region, $key);
        $property = new \ReflectionProperty($this->region, 'lockLifetime');

        $property->setAccessible(true);
        $property->setValue($this->region, -10);

        $this->assertFalse($this->region->contains($key));
        $this->assertTrue($this->region->put($key, $entry));
        $this->assertTrue($this->region->contains($key));

        $this->assertInstanceOf('Doctrine\ORM\Cache\Lock', $lock = $this->region->lock($key));
        $this->assertEquals($lock->value, file_get_contents($file));
        $this->assertFileExists($file);

        // outdated lock should be removed
        $this->assertTrue($this->region->contains($key));
        $this->assertNotNull($this->region->get($key));
        $this->assertFileNotExists($file);
    }
}