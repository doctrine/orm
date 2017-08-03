<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * IndexBy ::= "INDEX" "BY" SimpleStateFieldPathExpression
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class IndexBy extends Node
{
    /**
     * @var PathExpression
     */
    public $simpleStateFieldPathExpression = null;

    /**
     * @param PathExpression $simpleStateFieldPathExpression
     */
    public function __construct($simpleStateFieldPathExpression)
    {
        $this->simpleStateFieldPathExpression = $simpleStateFieldPathExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkIndexBy($this);
    }
}
