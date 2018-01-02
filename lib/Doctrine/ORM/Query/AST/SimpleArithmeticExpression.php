<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * SimpleArithmeticExpression ::= ArithmeticTerm {("+" | "-") ArithmeticTerm}*
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class SimpleArithmeticExpression extends Node
{
    /**
     * @var array
     */
    public $arithmeticTerms = [];

    /**
     * @param array $arithmeticTerms
     */
    public function __construct(array $arithmeticTerms)
    {
        $this->arithmeticTerms = $arithmeticTerms;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkSimpleArithmeticExpression($this);
    }
}
