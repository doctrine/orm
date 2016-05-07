<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\ConcurrentRegion;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Cache\Region\FileLockRegion;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        $this->cleanTestDirectory($this->directory);
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
        self::assertEquals('concurren_region_test', $this->region->getName());
    }

    public function testLockAndUnlock()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(['foo' => 'bar']);
        $file   = $this->getFileName($this->region, $key);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        $lock = $this->region->lock($key);

        self::assertFileExists($file);
        self::assertInstanceOf(Lock::class, $lock);
        self::assertEquals($lock->value, file_get_contents($file));

        // should be not available after lock
        self::assertFalse($this->region->contains($key));
        self::assertNull($this->region->get($key));

        self::assertTrue($this->region->unlock($key, $lock));
        self::assertFileNotExists($file);
    }

    public function testLockWithExistingLock()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(['foo' => 'bar']);
        $file   = $this->getFileName($this->region, $key);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        file_put_contents($file, 'foo');
        self::assertFileExists($file);
        self::assertEquals('foo' , file_get_contents($file));

        self::assertNull($this->region->lock($key));
        self::assertEquals('foo' , file_get_contents($file));
        self::assertFileExists($file);

        // should be not available
        self::assertFalse($this->region->contains($key));
        self::assertNull($this->region->get($key));
    }

    public function testUnlockWithExistingLock()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(['foo' => 'bar']);
        $file   = $this->getFileName($this->region, $key);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        self::assertInstanceOf(Lock::class, $lock = $this->region->lock($key));
        self::assertEquals($lock->value, file_get_contents($file));
        self::assertFileExists($file);

        // change the lock
        file_put_contents($file, 'foo');
        self::assertFileExists($file);
        self::assertEquals('foo' , file_get_contents($file));

        //try to unlock
        self::assertFalse($this->region->unlock($key, $lock));
        self::assertEquals('foo' , file_get_contents($file));
        self::assertFileExists($file);

        // should be not available
        self::assertFalse($this->region->contains($key));
        self::assertNull($this->region->get($key));
    }

    public function testPutWithExistingLock()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(['foo' => 'bar']);
        $file   = $this->getFileName($this->region, $key);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        // create lock
        file_put_contents($file, 'foo');
        self::assertFileExists($file);
        self::assertEquals('foo' , file_get_contents($file));

        self::assertFalse($this->region->contains($key));
        self::assertFalse($this->region->put($key, $entry));
        self::assertFalse($this->region->contains($key));

        self::assertFileExists($file);
        self::assertEquals('foo' , file_get_contents($file));
    }

    public function testLockedEvict()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(['foo' => 'bar']);
        $file   = $this->getFileName($this->region, $key);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        self::assertInstanceOf(Lock::class, $lock = $this->region->lock($key));
        self::assertEquals($lock->value, file_get_contents($file));
        self::assertFileExists($file);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->evict($key));
        self::assertFalse($this->region->contains($key));
        self::assertFileNotExists($file);
    }

    public function testLockedEvictAll()
    {
        $key1    = new CacheKeyMock('key1');
        $entry1  = new CacheEntryMock(['foo1' => 'bar1']);
        $file1   = $this->getFileName($this->region, $key1);

        $key2    = new CacheKeyMock('key2');
        $entry2  = new CacheEntryMock(['foo2' => 'bar2']);
        $file2   = $this->getFileName($this->region, $key2);

        self::assertFalse($this->region->contains($key1));
        self::assertTrue($this->region->put($key1, $entry1));
        self::assertTrue($this->region->contains($key1));

        self::assertFalse($this->region->contains($key2));
        self::assertTrue($this->region->put($key2, $entry2));
        self::assertTrue($this->region->contains($key2));

        self::assertInstanceOf(Lock::class, $lock1 = $this->region->lock($key1));
        self::assertInstanceOf(Lock::class, $lock2 = $this->region->lock($key2));

        self::assertEquals($lock2->value, file_get_contents($file2));
        self::assertEquals($lock1->value, file_get_contents($file1));

        self::assertFileExists($file1);
        self::assertFileExists($file2);

        self::assertTrue($this->region->evictAll());

        self::assertFileNotExists($file1);
        self::assertFileNotExists($file2);

        self::assertFalse($this->region->contains($key1));
        self::assertFalse($this->region->contains($key2));
    }

    public function testLockLifetime()
    {
        $key    = new CacheKeyMock('key');
        $entry  = new CacheEntryMock(['foo' => 'bar']);
        $file   = $this->getFileName($this->region, $key);
        $property = new \ReflectionProperty($this->region, 'lockLifetime');

        $property->setAccessible(true);
        $property->setValue($this->region, -10);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        self::assertInstanceOf(Lock::class, $lock = $this->region->lock($key));
        self::assertEquals($lock->value, file_get_contents($file));
        self::assertFileExists($file);

        // outdated lock should be removed
        self::assertTrue($this->region->contains($key));
        self::assertNotNull($this->region->get($key));
        self::assertFileNotExists($file);
    }

    /**
     * @group 1072
     * @group DDC-3191
     */
    public function testHandlesScanErrorsGracefullyOnEvictAll()
    {
        $region              = $this->createRegion();
        $reflectionDirectory = new \ReflectionProperty($region, 'directory');

        $reflectionDirectory->setAccessible(true);
        $reflectionDirectory->setValue($region, str_repeat('a', 10000));

        set_error_handler(function () {}, E_WARNING);
        self::assertTrue($region->evictAll());
        restore_error_handler();
    }

    /**
     * @param string|null $path directory to clean
     */
    private function cleanTestDirectory($path)
    {
        $path = $path ?: $this->directory;

        if ( ! is_dir($path)) {
            return;
        }

        $directoryIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($directoryIterator as $file) {
            if ($file->isFile()) {
                @unlink($file->getRealPath());
            } else {
                @rmdir($file->getRealPath());
            }
        }
    }
}
