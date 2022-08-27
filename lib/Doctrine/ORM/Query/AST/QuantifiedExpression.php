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
    /** @var string */
    public $type;

    /** @param Subselect $subselect */
    public function __construct(public $subselect)
    {
    }

    /** @return bool */
    public function isAll()
    {
        return strtoupper($this->type) === 'ALL';
    }

    /** @return bool */
    public function isAny()
    {
        return strtoupper($this->type) === 'ANY';
    }

    /** @return bool */
    public function isSome()
    {
        return strtoupper($this->type) === 'SOME';
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkQuantifiedExpression($this);
    }
}
