<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL select statements.
 *
 * @link    www.doctrine-project.org
 */
class Select extends Base
{
    protected string $preSeparator  = '';
    protected string $postSeparator = '';

    /** @var string[] */
    protected array $allowedClasses = [Func::class];

    /** @psalm-var list<string|Func> */
    protected array $parts = [];

    /** @psalm-return list<string|Func> */
    public function getParts(): array
    {
        return $this->parts;
    }
}
