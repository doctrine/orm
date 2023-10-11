<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL OR clauses.
 *
 * @link    www.doctrine-project.org
 */
class Orx extends Composite
{
    protected string $separator = ' OR ';

    /** @var string[] */
    protected array $allowedClasses = [
        Comparison::class,
        Func::class,
        Andx::class,
        self::class,
    ];

    /** @psalm-var list<string|Comparison|Func|Andx|self> */
    protected array $parts = [];

    /** @psalm-return list<string|Comparison|Func|Andx|self> */
    public function getParts(): array
    {
        return $this->parts;
    }
}
