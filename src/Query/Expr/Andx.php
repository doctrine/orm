<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use Stringable;

/**
 * Expression class for building DQL and parts.
 *
 * @link    www.doctrine-project.org
 */
class Andx extends Composite
{
    /** @var string */
    protected $separator = ' AND ';

    /** @var list<class-string<Stringable>> */
    protected $allowedClasses = [
        Comparison::class,
        Func::class,
        Orx::class,
        self::class,
    ];

    /** @phpstan-var list<string|Comparison|Func|Orx|self> */
    protected $parts = [];

    /** @phpstan-return list<string|Comparison|Func|Orx|self> */
    public function getParts()
    {
        return $this->parts;
    }
}
