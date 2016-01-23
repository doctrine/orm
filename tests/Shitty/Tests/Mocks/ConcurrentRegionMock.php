<?php

namespace Shitty\Tests\Mocks;

use Shitty\ORM\Cache\CollectionCacheEntry;
use Shitty\ORM\Cache\ConcurrentRegion;
use Shitty\ORM\Cache\LockException;
use Shitty\ORM\Cache\CacheEntry;
use Shitty\ORM\Cache\CacheKey;
use Shitty\ORM\Cache\Region;
use Shitty\ORM\Cache\Lock;

/**
 * Concurrent region mock
 *
 * Used to mock a ConcurrentRegion
 */
class ConcurrentRegionMock implements ConcurrentRegion
{
    public $calls       = array();
    public $exceptions  = array();
    public $locks       = array();

    /**
     * @var \Shitty\ORM\Cache\Region
     */
    private $region;

    /**
     * @param \Shitty\ORM\Cache\Region $region
     */
    public function __construct(Region $region)
    {
        $this->region = $region;
    }

    /**
     * Dequeue an exception for a specific method invocation
     *
     * @param string $method
     * @param mixed $default
     *
     * @return mixed
     */
    private function throwException($method)
    {
        if (isset($this->exceptions[$method]) && ! empty($this->exceptions[$method])) {
            $exception = array_shift($this->exceptions[$method]);

            if ($exception != null) {
                throw $exception;
            }
        }
    }

    /**
     * Queue an exception for the next method invocation
     *
     * @param string $method
     * @param \Exception $e
     */
    public function addException($method, \Exception $e)
    {
        $this->exceptions[$method][] = $e;
    }

    /**
     * Locks a specific cache entry
     *
     * @param \Shitty\ORM\Cache\CacheKey $key
     * @param \Shitty\ORM\Cache\Lock $lock
     */
    public function setLock(CacheKey $key, Lock $lock)
    {
        $this->locks[$key->hash] = $lock;
    }

    /**
     * {@inheritdoc}
     */
    public function contains(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        if (isset($this->locks[$key->hash])) {
            return false;
        }

        $this->throwException(__FUNCTION__);

        return $this->region->contains($key);
    }

    /**
     * {@inheritdoc}
     */
    public function evict(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        $this->throwException(__FUNCTION__);

        return $this->region->evict($key);
    }

    /**
     * {@inheritdoc}
     */
    public function evictAll()
    {
        $this->calls[__FUNCTION__][] = array();

        $this->throwException(__FUNCTION__);

        return $this->region->evictAll();
    }

    /**
     * {@inheritdoc}
     */
    public function get(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        $this->throwException(__FUNCTION__);

        if (isset($this->locks[$key->hash])) {
            return null;
        }

        return $this->region->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(CollectionCacheEntry $collection)
    {
        $this->calls[__FUNCTION__][] = array('collection' => $collection);

        $this->throwException(__FUNCTION__);

        return $this->region->getMultiple($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        $this->calls[__FUNCTION__][] = array();

        $this->throwException(__FUNCTION__);

        return $this->region->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function put(CacheKey $key, CacheEntry $entry, Lock $lock = null)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key, 'entry' => $entry);

        $this->throwException(__FUNCTION__);

        if (isset($this->locks[$key->hash])) {

            if ($lock !== null && $this->locks[$key->hash]->value === $lock->value) {
                return $this->region->put($key, $entry);
            }

            return false;
        }

        return $this->region->put($key, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function lock(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        $this->throwException(__FUNCTION__);

        if (isset($this->locks[$key->hash])) {
            return null;
        }

        return $this->locks[$key->hash] = Lock::createLockRead();
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(CacheKey $key, Lock $lock)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key, 'lock' => $lock);

        $this->throwException(__FUNCTION__);

        if ( ! isset($this->locks[$key->hash])) {
            return;
        }

        if ($this->locks[$key->hash]->value !== $lock->value) {
            throw LockException::unexpectedLockValue($lock);
        }

        unset($this->locks[$key->hash]);
    }
}
