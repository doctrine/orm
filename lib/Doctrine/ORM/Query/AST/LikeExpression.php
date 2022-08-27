<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\SqlWalker;

/**
 * LikeExpression ::= StringExpression ["NOT"] "LIKE" string ["ESCAPE" char]
 *
 * @link    www.doctrine-project.org
 */
class LikeExpression extends Node
{
    /** @var bool */
    public $not = false;

    /**
     * @param Node|string                                        $stringExpression
     * @param InputParameter|FunctionNode|PathExpression|Literal $stringPattern
     * @param Literal|null                                       $escapeChar
     */
    public function __construct(public $stringExpression, public $stringPattern, public $escapeChar = null)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkLikeExpression($this);
    }
}
