<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * SimpleSelectClause  ::= "SELECT" ["DISTINCT"] SimpleSelectExpression
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class SimpleSelectClause extends Node
{
    /**
     * @var bool
     */
    public $isDistinct = false;

    /**
     * @var SimpleSelectExpression
     */
    public $simpleSelectExpression;

    /**
     * @param SimpleSelectExpression $simpleSelectExpression
     * @param bool                   $isDistinct
     */
    public function __construct($simpleSelectExpression, $isDistinct)
    {
        $this->simpleSelectExpression = $simpleSelectExpression;
        $this->isDistinct = $isDistinct;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkSimpleSelectClause($this);
    }
}
