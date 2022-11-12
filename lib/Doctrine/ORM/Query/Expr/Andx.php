<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL and parts.
 *
 * @link    www.doctrine-project.org
 */
class Andx extends Composite
{
    protected string $separator = ' AND ';

    /** @var string[] */
    protected array $allowedClasses = [
        Comparison::class,
        Func::class,
        Orx::class,
        self::class,
    ];

    /** @psalm-var list<string|Comparison|Func|Orx|self> */
    protected array $parts = [];

    /** @psalm-return list<string|Comparison|Func|Orx|self> */
    public function getParts(): array
    {
        return $this->parts;
    }
}
