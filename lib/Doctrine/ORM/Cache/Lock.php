<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * Cache Lock
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class Lock
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var integer
     */
    public $time;

    /**
     * @param string  $value
     * @param integer $time
     */
    public function __construct($value, $time = null)
    {
        $this->value = $value;
        $this->time  = $time ? : time();
    }

    /**
     * @return \Doctrine\ORM\Cache\Lock
     */
    public static function createLockRead()
    {
        return new self(uniqid((string) time(), true));
    }
}
