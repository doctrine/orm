<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region;

class CacheRegionMock implements Region
{
    public $calls   = array();
    public $returns = array();
    public $name;
    
    public function addReturn($method, $value)
    {
        $this->returns[$method][] = $value;
    }

    public function getReturn($method, $default)
    {
        if (isset($this->returns[$method]) && ! empty($this->returns[$method])) {
            return array_shift($this->returns[$method]);
        }

        return $default;
    }

    public function getName()
    {
        $this->calls[__FUNCTION__][] = array();

        return $this->name;
    }

    public function contains(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        return $this->getReturn(__FUNCTION__, false);
    }

    public function evict(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        return $this->getReturn(__FUNCTION__, true);
    }

    public function evictAll()
    {
        $this->calls[__FUNCTION__][] = array();

        return $this->getReturn(__FUNCTION__, true);
    }

    public function get(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        return $this->getReturn(__FUNCTION__, null);
    }

    public function put(CacheKey $key, CacheEntry $entry, Lock $lock = null)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key, 'entry' => $entry);

        return $this->getReturn(__FUNCTION__, true);
    }

    public function clear()
    {
        $this->calls   = array();
        $this->returns = array();
    }
}
