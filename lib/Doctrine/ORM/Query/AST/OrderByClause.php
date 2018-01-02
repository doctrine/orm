<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * OrderByClause ::= "ORDER" "BY" OrderByItem {"," OrderByItem}*
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class OrderByClause extends Node
{
    /**
     * @var array
     */
    public $orderByItems = [];

    /**
     * @param array $orderByItems
     */
    public function __construct(array $orderByItems)
    {
        $this->orderByItems = $orderByItems;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkOrderByClause($this);
    }
}
