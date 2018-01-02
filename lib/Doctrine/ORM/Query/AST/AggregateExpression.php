<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * Description of AggregateExpression.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class AggregateExpression extends Node
{
    /**
     * @var string
     */
    public $functionName;

    /**
     * @var PathExpression|SimpleArithmeticExpression
     */
    public $pathExpression;

    /**
     * Some aggregate expressions support distinct, eg COUNT.
     *
     * @var bool
     */
    public $isDistinct = false;

    /**
     * @param string                                    $functionName
     * @param PathExpression|SimpleArithmeticExpression $pathExpression
     * @param bool                                      $isDistinct
     */
    public function __construct($functionName, $pathExpression, $isDistinct)
    {
        $this->functionName = $functionName;
        $this->pathExpression = $pathExpression;
        $this->isDistinct = $isDistinct;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkAggregateExpression($this);
    }
}
