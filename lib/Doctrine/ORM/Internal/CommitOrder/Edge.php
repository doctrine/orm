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
    /**
     * @var string
     * @readonly
     */
    public $from;

    /**
     * @var string
     * @readonly
     */
    public $to;

    /**
     * @var int
     * @readonly
     */
    public $weight;

    public function __construct(string $from, string $to, int $weight)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/10547',
            'The %s class is deprecated and will be removed in ORM 3.0',
            self::class
        );

        $this->from   = $from;
        $this->to     = $to;
        $this->weight = $weight;
    }
}
