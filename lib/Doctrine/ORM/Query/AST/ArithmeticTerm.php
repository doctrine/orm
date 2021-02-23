<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ArithmeticTerm ::= ArithmeticFactor {("*" | "/") ArithmeticFactor}*
 */
class ArithmeticTerm extends Node
{
    /** @var ArithmeticFactor[] */
    public $arithmeticFactors;

    /**
     * @param ArithmeticFactor[] $arithmeticFactors
     */
    public function __construct(array $arithmeticFactors)
    {
        $this->arithmeticFactors = $arithmeticFactors;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkArithmeticTerm($this);
    }
}
