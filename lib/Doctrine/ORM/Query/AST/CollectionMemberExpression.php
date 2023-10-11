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
    /** @param PathExpression $collectionValuedPathExpression */
    public function __construct(
        public mixed $entityExpression,
        public $collectionValuedPathExpression,
        public bool $not = false,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkCollectionMemberExpression($this);
    }
}
