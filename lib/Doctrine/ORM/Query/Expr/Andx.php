<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL and parts.
 */
class Andx extends Composite
{
    /** @var string */
    protected $separator = ' AND ';

    /** @var string[] */
    protected $allowedClasses = [
        Comparison::class,
        Func::class,
        Orx::class,
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
