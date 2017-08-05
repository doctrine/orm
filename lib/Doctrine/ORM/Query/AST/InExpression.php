<?php
declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * InExpression ::= StateFieldPathExpression ["NOT"] "IN" "(" (Literal {"," Literal}* | Subselect) ")"
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class InExpression extends Node
{
    /**
     * @var bool
     */
    public $not;

    /**
     * @var ArithmeticExpression
     */
    public $expression;

    /**
     * @var array
     */
    public $literals = [];

    /**
     * @var Subselect|null
     */
    public $subselect;

    /**
     * @param ArithmeticExpression $expression
     */
    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkInExpression($this);
    }
}
