<?php

namespace Shitty\Tests\Mocks;

use Shitty\ORM\Cache\CacheEntry;
use Shitty\ORM\Cache\CacheKey;
use Shitty\ORM\Cache\CollectionCacheEntry;
use Shitty\ORM\Cache\Lock;
use Shitty\ORM\Cache\Region;

/**
 * Cache region mock
 */
class CacheRegionMock implements Region
{
    public $calls   = array();
    public $returns = array();
    public $name;

    /**
     * Queue a return value for a specific method invocation
     *
     * @param string $method
     * @param mixed $value
     */
    public function addReturn($method, $value)
    {
        $this->returns[$method][] = $value;
    }

    /**
     * Dequeue a value for a specific method invocation
     *
     * @param string $method
     * @param mixed $default
     *
     * @return mixed
     */
    private function getReturn($method, $default)
    {
        if (isset($this->returns[$method]) && ! empty($this->returns[$method])) {
            return array_shift($this->returns[$method]);
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        $this->calls[__FUNCTION__][] = array();

        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function contains(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        return $this->getReturn(__FUNCTION__, false);
    }

    /**
     * {@inheritdoc}
     */
    public function evict(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        return $this->getReturn(__FUNCTION__, true);
    }

    /**
     * {@inheritdoc}
     */
    public function evictAll()
    {
        $this->calls[__FUNCTION__][] = array();

        return $this->getReturn(__FUNCTION__, true);
    }

    /**
     * {@inheritdoc}
     */
    public function get(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);

        return $this->getReturn(__FUNCTION__, null);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(CollectionCacheEntry $collection)
    {
        $this->calls[__FUNCTION__][] = array('collection' => $collection);

        return $this->getReturn(__FUNCTION__, null);
    }

    /**
     * {@inheritdoc}
     */
    public function put(CacheKey $key, CacheEntry $entry, Lock $lock = null)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key, 'entry' => $entry);

        return $this->getReturn(__FUNCTION__, true);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->calls   = array();
        $this->returns = array();
    }
}
