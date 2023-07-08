<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\CommitOrder;

use Doctrine\Deprecations\Deprecation;

/**
 * @internal
 * @deprecated
 */
final class VertexState
{
    public const NOT_VISITED = 0;
    public const IN_PROGRESS = 1;
    public const VISITED     = 2;

    private function __construct()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/10547',
            'The %s class is deprecated and will be removed in ORM 3.0',
            self::class
        );
    }
}
