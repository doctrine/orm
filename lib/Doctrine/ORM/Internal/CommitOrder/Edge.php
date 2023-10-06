<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\CommitOrder;

use Doctrine\Deprecations\Deprecation;

/**
 * @internal
 * @deprecated
 */
final class Edge
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly int $weight,
    ) {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/10547',
            'The %s class is deprecated and will be removed in ORM 3.0',
            self::class,
        );
    }
}
