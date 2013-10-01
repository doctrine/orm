<?php

namespace Doctrine\Tests\Mocks;


use Doctrine\ORM\Cache\ConcurrentRegion;
use Doctrine\ORM\Cache\LockException;
use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\Lock;

class ConcurrentRegionMock implements ConcurrentRegion
{
    public $calls       = array();
    public $exceptions  = array();
    public $locks       = array();
    
    /**
     * @var \Doctrine\ORM\Cache\Region 
     */
    private $region;

    public function __construct(Region $region)
    {
        $this->region = $region;
    }

    private function throwException($method)
    {
        if (isset($this->exceptions[$method]) && ! empty($this->exceptions[$method])) {
            $exception = array_shift($this->exceptions[$method]);

            if($exception != null) {
                throw $exception;
            }
        }
    }

    public function addException($method, \Exception $e)
    {
        $this->exceptions[$method][] = $e;
    }

    public function setLock(CacheKey $key, Lock $lock)
    {
        $this->locks[$key->hash] = $lock;
    }

    public function contains(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        if (isset($this->locks[$key->hash])) {
            return false;
        }

        $this->throwException(__FUNCTION__);

        return $this->region->contains($key);
    }

    public function evict(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        $this->throwException(__FUNCTION__);

        return $this->region->evict($key);
    }

    public function evictAll()
    {
        $this->calls[__FUNCTION__][] = array();

        $this->throwException(__FUNCTION__);

        return $this->region->evictAll();
    }

    public function get(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        $this->throwException(__FUNCTION__);

        if (isset($this->locks[$key->hash])) {
            return null;
        }

        return $this->region->get($key);
    }

    public function getName()
    {
        $this->calls[__FUNCTION__][] = array();

        $this->throwException(__FUNCTION__);

        return $this->region->getName();
    }

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

    public function lock(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        $this->throwException(__FUNCTION__);

        if (isset($this->locks[$key->hash])) {
            return null;
        }

        return $this->locks[$key->hash] = Lock::createLockRead();
    }

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
