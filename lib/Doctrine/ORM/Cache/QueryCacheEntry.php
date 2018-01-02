<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * Query cache entry
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class QueryCacheEntry implements CacheEntry
{
    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var array List of entity identifiers
     */
    public $result;

    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var float Time creation of this cache entry
     */
    public $time;

    /**
     * @param array $result
     * @param float $time
     */
    public function __construct($result, $time = null)
    {
        $this->result = $result;
        $this->time   = $time ?: microtime(true);
    }

    /**
     * @param array $values
     *
     * @return QueryCacheEntry
     */
    public static function __set_state(array $values)
    {
        return new self($values['result'], $values['time']);
    }
}
