<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * CollectionMemberExpression ::= EntityExpression ["NOT"] "MEMBER" ["OF"] CollectionValuedPathExpression
 *
 * @link    www.doctrine-project.org
 */
class CollectionMemberExpression extends Node
{
    /** @var mixed */
    public $entityExpression;

    /** @var PathExpression */
    public $collectionValuedPathExpression;

    /** @var bool */
    public $not;

    /**
     * @param mixed          $entityExpr
     * @param PathExpression $collValuedPathExpr
     */
    public function __construct($entityExpr, $collValuedPathExpr)
    {
        $this->entityExpression               = $entityExpr;
        $this->collectionValuedPathExpression = $collValuedPathExpr;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkCollectionMemberExpression($this);
    }
}
