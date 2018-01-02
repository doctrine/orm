<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * ArithmeticTerm ::= ArithmeticFactor {("*" | "/") ArithmeticFactor}*
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ArithmeticTerm extends Node
{
    /**
     * @var array
     */
    public $arithmeticFactors;

    /**
     * @param array $arithmeticFactors
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
