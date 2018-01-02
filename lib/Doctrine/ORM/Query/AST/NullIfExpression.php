<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * NullIfExpression ::= "NULLIF" "(" ScalarExpression "," ScalarExpression ")"
 *
 * @since   2.1
 * 
 * @link    www.doctrine-project.org
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class NullIfExpression extends Node
{
    /**
     * @var mixed
     */
    public $firstExpression;

    /**
     * @var mixed
     */
    public $secondExpression;

    /**
     * @param mixed $firstExpression
     * @param mixed $secondExpression
     */
    public function __construct($firstExpression, $secondExpression)
    {
        $this->firstExpression  = $firstExpression;
        $this->secondExpression = $secondExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkNullIfExpression($this);
    }
}
