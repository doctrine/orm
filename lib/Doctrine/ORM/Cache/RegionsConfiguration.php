<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * Cache regions configuration
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class RegionsConfiguration
{
    /**
     * @var array
     */
    private $lifetimes = [];

    /**
     * @var array
     */
    private $lockLifetimes = [];

    /**
     * @var integer
     */
    private $defaultLifetime;

    /**
     * @var integer
     */
    private $defaultLockLifetime;

    /**
     * @param integer $defaultLifetime
     * @param integer $defaultLockLifetime
     */
    public function __construct($defaultLifetime = 3600, $defaultLockLifetime = 60)
    {
        $this->defaultLifetime      = (integer) $defaultLifetime;
        $this->defaultLockLifetime  = (integer) $defaultLockLifetime;
    }

    /**
     * @return integer
     */
    public function getDefaultLifetime()
    {
        return $this->defaultLifetime;
    }

    /**
     * @param integer $defaultLifetime
     */
    public function setDefaultLifetime($defaultLifetime)
    {
        $this->defaultLifetime = (integer) $defaultLifetime;
    }

    /**
     * @return integer
     */
    public function getDefaultLockLifetime()
    {
        return $this->defaultLockLifetime;
    }

    /**
     * @param integer $defaultLockLifetime
     */
    public function setDefaultLockLifetime($defaultLockLifetime)
    {
        $this->defaultLockLifetime = (integer) $defaultLockLifetime;
    }

    /**
     * @param string $regionName
     *
     * @return integer
     */
    public function getLifetime($regionName)
    {
        return $this->lifetimes[$regionName] ?? $this->defaultLifetime;
    }

    /**
     * @param string  $name
     * @param integer $lifetime
     */
    public function setLifetime($name, $lifetime)
    {
        $this->lifetimes[$name] = (integer) $lifetime;
    }

    /**
     * @param string $regionName
     *
     * @return integer
     */
    public function getLockLifetime($regionName)
    {
        return $this->lockLifetimes[$regionName] ?? $this->defaultLockLifetime;
    }

    /**
     * @param string  $name
     * @param integer $lifetime
     */
    public function setLockLifetime($name, $lifetime)
    {
        $this->lockLifetimes[$name] = (integer) $lifetime;
    }
}
