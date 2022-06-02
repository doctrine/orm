<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Cache\Region\FileLockRegion;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use ReflectionProperty;

use function file_put_contents;
use function is_dir;
use function restore_error_handler;
use function rmdir;
use function set_error_handler;
use function str_repeat;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const E_WARNING;

/**
 * @extends RegionTestCase<FileLockRegion>
 * @group DDC-2183
 */
class FileLockRegionTest extends RegionTestCase
{
    /** @var string */
    protected $directory;

    public function tearDown(): void
    {
        $this->cleanTestDirectory($this->directory);
    }

    private function getFileName(FileLockRegion $region, CacheKey $key): string
    {
        $reflection = new ReflectionMethod($region, 'getLockFileName');

        $reflection->setAccessible(true);

        return $reflection->invoke($region, $key);
    }

    protected function createRegion(): Region
    {
        $this->directory = sys_get_temp_dir() . '/doctrine_lock_' . uniqid();

        $region = new DefaultRegion('concurren_region_test', $this->cacheItemPool);

        return new FileLockRegion($region, $this->directory, 60);
    }

    public function testGetRegionName(): void
    {
        self::assertEquals('concurren_region_test', $this->region->getName());
    }

    public function testLockAndUnlock(): void
    {
        $key   = new CacheKeyMock('key');
        $entry = new CacheEntryMock(['foo' => 'bar']);
        $file  = $this->getFileName($this->region, $key);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        $lock = $this->region->lock($key);

        self::assertFileExists($file);
        self::assertInstanceOf(Lock::class, $lock);
        self::assertStringEqualsFile($file, $lock->value);

        // should be not available after lock
        self::assertFalse($this->region->contains($key));
        self::assertNull($this->region->get($key));

        self::assertTrue($this->region->unlock($key, $lock));
        self::assertFileDoesNotExist($file);
    }

    public function testLockWithExistingLock(): void
    {
        $key   = new CacheKeyMock('key');
        $entry = new CacheEntryMock(['foo' => 'bar']);
        $file  = $this->getFileName($this->region, $key);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        file_put_contents($file, 'foo');
        self::assertFileExists($file);
        self::assertStringEqualsFile($file, 'foo');

        self::assertNull($this->region->lock($key));
        self::assertStringEqualsFile($file, 'foo');
        self::assertFileExists($file);

        // should be not available
        self::assertFalse($this->region->contains($key));
        self::assertNull($this->region->get($key));
    }

    public function testUnlockWithExistingLock(): void
    {
        $key   = new CacheKeyMock('key');
        $entry = new CacheEntryMock(['foo' => 'bar']);
        $file  = $this->getFileName($this->region, $key);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        self::assertInstanceOf(Lock::class, $lock = $this->region->lock($key));
        self::assertStringEqualsFile($file, $lock->value);
        self::assertFileExists($file);

        // change the lock
        file_put_contents($file, 'foo');
        self::assertFileExists($file);
        self::assertStringEqualsFile($file, 'foo');

        //try to unlock
        self::assertFalse($this->region->unlock($key, $lock));
        self::assertStringEqualsFile($file, 'foo');
        self::assertFileExists($file);

        // should be not available
        self::assertFalse($this->region->contains($key));
        self::assertNull($this->region->get($key));
    }

    public function testPutWithExistingLock(): void
    {
        $key   = new CacheKeyMock('key');
        $entry = new CacheEntryMock(['foo' => 'bar']);
        $file  = $this->getFileName($this->region, $key);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        // create lock
        file_put_contents($file, 'foo');
        self::assertFileExists($file);
        self::assertStringEqualsFile($file, 'foo');

        self::assertFalse($this->region->contains($key));
        self::assertFalse($this->region->put($key, $entry));
        self::assertFalse($this->region->contains($key));

        self::assertFileExists($file);
        self::assertStringEqualsFile($file, 'foo');
    }

    public function testLockedEvict(): void
    {
        $key   = new CacheKeyMock('key');
        $entry = new CacheEntryMock(['foo' => 'bar']);
        $file  = $this->getFileName($this->region, $key);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        self::assertInstanceOf(Lock::class, $lock = $this->region->lock($key));
        self::assertStringEqualsFile($file, $lock->value);
        self::assertFileExists($file);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->evict($key));
        self::assertFalse($this->region->contains($key));
        self::assertFileDoesNotExist($file);
    }

    public function testLockedEvictAll(): void
    {
        $key1   = new CacheKeyMock('key1');
        $entry1 = new CacheEntryMock(['foo1' => 'bar1']);
        $file1  = $this->getFileName($this->region, $key1);

        $key2   = new CacheKeyMock('key2');
        $entry2 = new CacheEntryMock(['foo2' => 'bar2']);
        $file2  = $this->getFileName($this->region, $key2);

        self::assertFalse($this->region->contains($key1));
        self::assertTrue($this->region->put($key1, $entry1));
        self::assertTrue($this->region->contains($key1));

        self::assertFalse($this->region->contains($key2));
        self::assertTrue($this->region->put($key2, $entry2));
        self::assertTrue($this->region->contains($key2));

        self::assertInstanceOf(Lock::class, $lock1 = $this->region->lock($key1));
        self::assertInstanceOf(Lock::class, $lock2 = $this->region->lock($key2));

        self::assertStringEqualsFile($file2, $lock2->value);
        self::assertStringEqualsFile($file1, $lock1->value);

        self::assertFileExists($file1);
        self::assertFileExists($file2);

        self::assertTrue($this->region->evictAll());

        self::assertFileDoesNotExist($file1);
        self::assertFileDoesNotExist($file2);

        self::assertFalse($this->region->contains($key1));
        self::assertFalse($this->region->contains($key2));
    }

    public function testLockLifetime(): void
    {
        $key      = new CacheKeyMock('key');
        $entry    = new CacheEntryMock(['foo' => 'bar']);
        $file     = $this->getFileName($this->region, $key);
        $property = new ReflectionProperty($this->region, 'lockLifetime');

        $property->setAccessible(true);
        $property->setValue($this->region, -10);

        self::assertFalse($this->region->contains($key));
        self::assertTrue($this->region->put($key, $entry));
        self::assertTrue($this->region->contains($key));

        self::assertInstanceOf(Lock::class, $lock = $this->region->lock($key));
        self::assertStringEqualsFile($file, $lock->value);
        self::assertFileExists($file);

        // outdated lock should be removed
        self::assertTrue($this->region->contains($key));
        self::assertNotNull($this->region->get($key));
        self::assertFileDoesNotExist($file);
    }

    /**
     * @group 1072
     * @group DDC-3191
     */
    public function testHandlesScanErrorsGracefullyOnEvictAll(): void
    {
        $region              = $this->createRegion();
        $reflectionDirectory = new ReflectionProperty($region, 'directory');

        $reflectionDirectory->setAccessible(true);
        $reflectionDirectory->setValue($region, str_repeat('a', 10000));

        set_error_handler(static function (): bool {
            return true;
        }, E_WARNING);
        try {
            self::assertTrue($region->evictAll());
        } finally {
            restore_error_handler();
        }
    }

    private function cleanTestDirectory(?string $path): void
    {
        $path = $path ?: $this->directory;

        if (! is_dir($path)) {
            return;
        }

        $directoryIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($directoryIterator as $file) {
            if ($file->isFile()) {
                @unlink((string) $file->getRealPath());
            } else {
                @rmdir((string) $file->getRealPath());
            }
        }
    }
}
