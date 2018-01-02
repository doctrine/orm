<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Defines the contract for caches capable of storing query results.
 * These caches should only concern themselves with storing the matching result ids.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface QueryCache
{
    /**
     * @return boolean
     */
    public function clear();

    /**
     * @param \Doctrine\ORM\Cache\QueryCacheKey    $key
     * @param \Doctrine\ORM\Query\ResultSetMapping $rsm
     * @param mixed                                $result
     * @param array                                $hints
     *
     * @return boolean
     */
    public function put(QueryCacheKey $key, ResultSetMapping $rsm, $result, array $hints = []);

    /**
     * @param \Doctrine\ORM\Cache\QueryCacheKey     $key
     * @param \Doctrine\ORM\Query\ResultSetMapping  $rsm
     * @param array                                 $hints
     *
     * @return array|null
     */
    public function get(QueryCacheKey $key, ResultSetMapping $rsm, array $hints = []);

    /**
     * @return \Doctrine\ORM\Cache\Region
     */
    public function getRegion();
}
