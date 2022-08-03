<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Region;

use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\ConcurrentRegion;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region;
use InvalidArgumentException;

use function array_filter;
use function array_map;
use function chmod;
use function file_get_contents;
use function file_put_contents;
use function fileatime;
use function glob;
use function is_dir;
use function is_file;
use function is_writable;
use function mkdir;
use function sprintf;
use function time;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;

/**
 * Very naive concurrent region, based on file locks.
 */
class FileLockRegion implements ConcurrentRegion
{
    public const LOCK_EXTENSION = 'lock';

    /**
     * @param numeric-string|int $lockLifetime
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly Region $region,
        private readonly string $directory,
        private readonly string|int $lockLifetime
    ) {
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true)) {
            throw new InvalidArgumentException(sprintf('The directory "%s" does not exist and could not be created.', $directory));
        }

        if (! is_writable($directory)) {
            throw new InvalidArgumentException(sprintf('The directory "%s" is not writable.', $directory));
        }
    }

    private function isLocked(CacheKey $key, ?Lock $lock = null): bool
    {
        $filename = $this->getLockFileName($key);

        if (! is_file($filename)) {
            return false;
        }

        $time    = $this->getLockTime($filename);
        $content = $this->getLockContent($filename);

        if (! $content || ! $time) {
            @unlink($filename);

            return false;
        }

        if ($lock && $content === $lock->value) {
            return false;
        }

        // outdated lock
        if ($time + $this->lockLifetime <= time()) {
            @unlink($filename);

            return false;
        }

        return true;
    }

    private function getLockFileName(CacheKey $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $key->hash . '.' . self::LOCK_EXTENSION;
    }

    private function getLockContent(string $filename): string|false
    {
        return @file_get_contents($filename);
    }

    private function getLockTime(string $filename): int|false
    {
        return @fileatime($filename);
    }

    public function getName(): string
    {
        return $this->region->getName();
    }

    public function contains(CacheKey $key): bool
    {
        if ($this->isLocked($key)) {
            return false;
        }

        return $this->region->contains($key);
    }

    public function get(CacheKey $key): ?CacheEntry
    {
        if ($this->isLocked($key)) {
            return null;
        }

        return $this->region->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(CollectionCacheEntry $collection): ?array
    {
        if (array_filter(array_map([$this, 'isLocked'], $collection->identifiers))) {
            return null;
        }

        return $this->region->getMultiple($collection);
    }

    public function put(CacheKey $key, CacheEntry $entry, ?Lock $lock = null): bool
    {
        if ($this->isLocked($key, $lock)) {
            return false;
        }

        return $this->region->put($key, $entry);
    }

    public function evict(CacheKey $key): bool
    {
        if ($this->isLocked($key)) {
            @unlink($this->getLockFileName($key));
        }

        return $this->region->evict($key);
    }

    public function evictAll(): bool
    {
        // The check below is necessary because on some platforms glob returns false
        // when nothing matched (even though no errors occurred)
        $filenames = glob(sprintf('%s/*.%s', $this->directory, self::LOCK_EXTENSION));

        if ($filenames) {
            foreach ($filenames as $filename) {
                @unlink($filename);
            }
        }

        return $this->region->evictAll();
    }

    public function lock(CacheKey $key): ?Lock
    {
        if ($this->isLocked($key)) {
            return null;
        }

        $lock     = Lock::createLockRead();
        $filename = $this->getLockFileName($key);

        if (! @file_put_contents($filename, $lock->value, LOCK_EX)) {
            return null;
        }

        chmod($filename, 0664);

        return $lock;
    }

    public function unlock(CacheKey $key, Lock $lock): bool
    {
        if ($this->isLocked($key, $lock)) {
            return false;
        }

        return @unlink($this->getLockFileName($key));
    }
}
