<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL OR clauses.
 */
class Orx extends Composite
{
    /** @var string */
    protected $separator = ' OR ';

    /** @var string[] */
    protected $allowedClasses = [
        Comparison::class,
        Func::class,
        Andx::class,
        self::class,
    ];

    /**
     * @return mixed[]
     */
    public function getParts()
    {
        return $this->parts;
    }
}
