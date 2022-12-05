<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

use function strtoupper;

/**
 * QuantifiedExpression ::= ("ALL" | "ANY" | "SOME") "(" Subselect ")"
 *
 * @link    www.doctrine-project.org
 */
class QuantifiedExpression extends Node
{
    public string $type;

    /** @param Subselect $subselect */
    public function __construct(public $subselect)
    {
    }

    public function isAll(): bool
    {
        return strtoupper($this->type) === 'ALL';
    }

    public function isAny(): bool
    {
        return strtoupper($this->type) === 'ANY';
    }

    public function isSome(): bool
    {
        return strtoupper($this->type) === 'SOME';
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkQuantifiedExpression($this);
    }
}
