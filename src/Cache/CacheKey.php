<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\Deprecations\Deprecation;

/**
 * Defines entity / collection / query key to be stored in the cache region.
 * Allows multiple roles to be stored in the same cache region.
 */
abstract class CacheKey
{
    /**
     * Unique identifier
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var string
     */
    public $hash;

    public function __construct(?string $hash = null)
    {
        if ($hash === null) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/10212',
                'Calling %s() without providing a value for the $hash parameter is deprecated.',
                __METHOD__
            );
        } else {
            $this->hash = $hash;
        }
    }
}
