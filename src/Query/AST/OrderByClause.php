<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * OrderByClause ::= "ORDER" "BY" OrderByItem {"," OrderByItem}*
 *
 * @link    www.doctrine-project.org
 */
class OrderByClause extends Node
{
    /** @var OrderByItem[] */
    public $orderByItems = [];
    /** @var bool */
    public $includeCollectionOrderByItems = true;

    /** @param OrderByItem[] $orderByItems */
    public function __construct(array $orderByItems, bool $includeCollectionOrderByItems = true)
    {
        $this->orderByItems                  = $orderByItems;
        $this->includeCollectionOrderByItems = $includeCollectionOrderByItems;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkOrderByClause($this);
    }
}
