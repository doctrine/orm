<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * OrderByItem ::= (ResultVariable | StateFieldPathExpression) ["ASC" | "DESC"]
 *
 * 
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class OrderByItem extends Node
{
    /**
     * @var mixed
     */
    public $expression;

    /**
     * @var string
     */
    public $type;

    /**
     * @param mixed $expression
     */
    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    /**
     * @return bool
     */
    public function isAsc()
    {
        return strtoupper($this->type) == 'ASC';
    }

    /**
     * @return bool
     */
    public function isDesc()
    {
        return strtoupper($this->type) == 'DESC';
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkOrderByItem($this);
    }
}
