<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * Description of GroupByClause.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class GroupByClause extends Node
{
    /**
     * @var array
     */
    public $groupByItems = [];

    /**
     * @param array $groupByItems
     */
    public function __construct(array $groupByItems)
    {
        $this->groupByItems = $groupByItems;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkGroupByClause($this);
    }
}
