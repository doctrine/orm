<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * Cache Lock
 */
class Lock
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var int
     */
    public $time;

    /**
     * @param string $value
     * @param int    $time
     */
    public function __construct($value, $time = null)
    {
        $this->value = $value;
        $this->time  = $time ?: time();
    }

    /**
     * @return \Doctrine\ORM\Cache\Lock
     */
    public static function createLockRead()
    {
        return new self(uniqid((string) time(), true));
    }
}
