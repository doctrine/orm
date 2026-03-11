<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use Stringable;

/**
 * Expression class for building DQL OR clauses.
 *
 * @link    www.doctrine-project.org
 */
class Orx extends Composite
{
    /** @var string */
    protected $separator = ' OR ';

    /** @var list<class-string<Stringable>> */
    protected $allowedClasses = [
        Comparison::class,
        Func::class,
        Andx::class,
        self::class,
    ];

    /** @phpstan-var list<string|Comparison|Func|Andx|self> */
    protected $parts = [];

    /** @phpstan-return list<string|Comparison|Func|Andx|self> */
    public function getParts()
    {
        return $this->parts;
    }
}
