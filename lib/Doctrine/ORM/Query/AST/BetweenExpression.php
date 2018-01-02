<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * Description of BetweenExpression.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class BetweenExpression extends Node
{
    /**
     * @var ArithmeticExpression
     */
    public $expression;

    /**
     * @var ArithmeticExpression
     */
    public $leftBetweenExpression;

    /**
     * @var ArithmeticExpression
     */
    public $rightBetweenExpression;

    /**
     * @var bool
     */
    public $not;

    /**
     * @param ArithmeticExpression $expr
     * @param ArithmeticExpression $leftExpr
     * @param ArithmeticExpression $rightExpr
     */
    public function __construct($expr, $leftExpr, $rightExpr)
    {
        $this->expression = $expr;
        $this->leftBetweenExpression = $leftExpr;
        $this->rightBetweenExpression = $rightExpr;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkBetweenExpression($this);
    }
}
