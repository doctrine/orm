<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Psr\Cache\CacheItemInterface;

/**
 * @internal this class is used as a workaround for a cache issue.
 *
 * @see https://github.com/doctrine/orm/pull/10095
 */
final class NullCacheItem implements CacheItemInterface
{
    public function getKey(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return null;
    }

    public function isHit(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time)
    {
        return $this;
    }
}
