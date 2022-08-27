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
    public const JOIN_TYPE_LEFT      = 1;
    public const JOIN_TYPE_LEFTOUTER = 2;
    public const JOIN_TYPE_INNER     = 3;

    /**
     * @var int
     * @psalm-var self::JOIN_TYPE_*
     */
    public $joinType = self::JOIN_TYPE_INNER;

    /** @var ConditionalExpression|null */
    public $conditionalExpression = null;

    /**
     * @param int  $joinType
     * @param Node $joinAssociationDeclaration
     * @psalm-param self::JOIN_TYPE_* $joinType
     */
    public function __construct($joinType, public $joinAssociationDeclaration = null)
    {
        $this->joinType = $joinType;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkJoin($this);
    }
}
