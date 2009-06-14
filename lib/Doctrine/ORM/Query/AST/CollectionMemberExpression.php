<?php

namespace Doctrine\ORM\Query\AST;

/**
 * CollectionMemberExpression ::= EntityExpression ["NOT"] "MEMBER" ["OF"] CollectionValuedPathExpression
 *
 * @author Roman Borschel <roman@code-factory.org>
 */
class CollectionMemberExpression extends Node
{
    public $entityExpression;
    public $collectionValuedPathExpression;
    public $isNot;

    public function __construct($entityExpr, $collValuedPathExpr, $isNot)
    {
        $this->entityExpression = $entityExpr;
        $this->collectionValuedPathExpression = $collValuedPathExpr;
        $this->isNot = $isNot;
    }

    public function dispatch($walker)
    {
        return $walker->walkCollectionMemberExpression($this);
    }
}

