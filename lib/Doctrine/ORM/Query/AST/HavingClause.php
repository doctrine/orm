<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * Description of HavingClause.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class HavingClause extends Node
{
    /**
     * @var ConditionalExpression
     */
    public $conditionalExpression;

    /**
     * @param ConditionalExpression $conditionalExpression
     */
    public function __construct($conditionalExpression)
    {
        $this->conditionalExpression = $conditionalExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkHavingClause($this);
    }
}
