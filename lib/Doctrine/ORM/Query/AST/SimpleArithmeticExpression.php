<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * SimpleArithmeticExpression ::= ArithmeticTerm {("+" | "-") ArithmeticTerm}*
 */
class SimpleArithmeticExpression extends Node
{
    /** @var ArithmeticTerm[] */
    public $arithmeticTerms = [];

    /**
     * @param ArithmeticTerm[] $arithmeticTerms
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
