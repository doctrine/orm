<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * IndexBy ::= "INDEX" "BY" SingleValuedPathExpression
 *
 * @link    www.doctrine-project.org
 */
class IndexBy extends Node
{
    /** @var PathExpression */
    public $singleValuedPathExpression = null;

    public function __construct(PathExpression $singleValuedPathExpression)
    {
        $this->singleValuedPathExpression = $singleValuedPathExpression;
    }

    public function dispatch(SqlWalker $walker): string
    {
        $walker->walkIndexBy($this);

        return '';
    }
}
