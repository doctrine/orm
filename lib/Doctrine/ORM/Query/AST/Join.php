<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * Join ::= ["LEFT" ["OUTER"] | "INNER"] "JOIN" JoinAssociationPathExpression
 *          ["AS"] AliasIdentificationVariable [("ON" | "WITH") ConditionalExpression]
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Join extends Node
{
    const JOIN_TYPE_LEFT      = 1;
    const JOIN_TYPE_LEFTOUTER = 2;
    const JOIN_TYPE_INNER     = 3;

    /**
     * @var int
     */
    public $joinType = self::JOIN_TYPE_INNER;

    /**
     * @var Node|null
     */
    public $joinAssociationDeclaration;

    /**
     * @var ConditionalExpression|null
     */
    public $conditionalExpression;

    /**
     * @param int  $joinType
     * @param Node $joinAssociationDeclaration
     */
    public function __construct($joinType, $joinAssociationDeclaration)
    {
        $this->joinType = $joinType;
        $this->joinAssociationDeclaration = $joinAssociationDeclaration;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkJoin($this);
    }
}
