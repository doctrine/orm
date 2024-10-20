<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for generating DQL functions.
 *
 * @link    www.doctrine-project.org
 */
class Literal extends Base
{
    protected string $preSeparator  = '';
    protected string $postSeparator = '';

    /** @psalm-var list<string> */
    protected array $parts = [];

    /** @psalm-return list<string> */
    public function getParts(): array
    {
        return $this->parts;
    }
}
