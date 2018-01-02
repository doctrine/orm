<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * SelectClause = "SELECT" ["DISTINCT"] SelectExpression {"," SelectExpression}
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class SelectClause extends Node
{
    /**
     * @var bool
     */
    public $isDistinct;

    /**
     * @var array
     */
    public $selectExpressions = [];

    /**
     * @param array $selectExpressions
     * @param bool  $isDistinct
     */
    public function __construct(array $selectExpressions, $isDistinct)
    {
        $this->isDistinct = $isDistinct;
        $this->selectExpressions = $selectExpressions;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkSelectClause($this);
    }
}
