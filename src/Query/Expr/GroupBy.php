<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL Group By parts.
 *
 * @link    www.doctrine-project.org
 */
class GroupBy extends Base
{
    protected string $preSeparator  = '';
    protected string $postSeparator = '';

    /** @phpstan-var list<string> */
    protected array $parts = [];

    /** @phpstan-return list<string> */
    public function getParts(): array
    {
        return $this->parts;
    }
}
