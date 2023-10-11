<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * Join ::= ["LEFT" ["OUTER"] | "INNER"] "JOIN" JoinAssociationPathExpression
 *          ["AS"] AliasIdentificationVariable [("ON" | "WITH") ConditionalExpression]
 *
 * @link    www.doctrine-project.org
 */
class Join extends Node
{
    final public const JOIN_TYPE_LEFT      = 1;
    final public const JOIN_TYPE_LEFTOUTER = 2;
    final public const JOIN_TYPE_INNER     = 3;

    /** @var ConditionalExpression|null */
    public $conditionalExpression = null;

    /**
     * @param Node $joinAssociationDeclaration
     * @psalm-param self::JOIN_TYPE_* $joinType
     */
    public function __construct(
        public int $joinType,
        public $joinAssociationDeclaration = null,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkJoin($this);
    }
}
