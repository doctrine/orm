<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class TimestampQueryCacheValidator implements QueryCacheValidator
{
    /**
     * @var TimestampRegion
     */
    private $timestampRegion;

    /**
     * @param TimestampRegion $timestampRegion
     */
    public function __construct(TimestampRegion $timestampRegion)
    {
        $this->timestampRegion = $timestampRegion;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(QueryCacheKey $key, QueryCacheEntry $entry)
    {
        if ($this->regionUpdated($key, $entry)) {
            return false;
        }

        if ($key->lifetime == 0) {
            return true;
        }

        return ($entry->time + $key->lifetime) > microtime(true);
    }

    /**
     * @param QueryCacheKey   $key
     * @param QueryCacheEntry $entry
     *
     * @return bool
     */
    private function regionUpdated(QueryCacheKey $key, QueryCacheEntry $entry)
    {
        if ($key->timestampKey === null) {
            return false;
        }

        $timestamp = $this->timestampRegion->get($key->timestampKey);

        return $timestamp && $timestamp->time > $entry->time;
    }
}
