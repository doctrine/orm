<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * LikeExpression ::= StringExpression ["NOT"] "LIKE" string ["ESCAPE" char]
 *
 * @link    www.doctrine-project.org
 */
class LikeExpression extends Node
{
    /** @var bool */
    public $not = false;

    /** @var Node|string */
    public $stringExpression;

    /** @var InputParameter|FunctionNode|PathExpression|Literal */
    public $stringPattern;

    /** @var Literal|null */
    public $escapeChar;

    /**
     * @param Node|string                                        $stringExpression
     * @param InputParameter|FunctionNode|PathExpression|Literal $stringPattern
     * @param Literal|null                                       $escapeChar
     */
    public function __construct($stringExpression, $stringPattern, $escapeChar = null, bool $not = false)
    {
        $this->stringExpression = $stringExpression;
        $this->stringPattern    = $stringPattern;
        $this->escapeChar       = $escapeChar;
        $this->not              = $not;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkLikeExpression($this);
    }
}
