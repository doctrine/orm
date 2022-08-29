<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * JoinAssociationDeclaration ::= JoinAssociationPathExpression ["AS"] AliasIdentificationVariable
 *
 * @link    www.doctrine-project.org
 */
class JoinAssociationDeclaration extends Node
{
    /**
     * @param JoinAssociationPathExpression $joinAssociationPathExpression
     * @param string                        $aliasIdentificationVariable
     * @param IndexBy|null                  $indexBy
     */
    public function __construct(public $joinAssociationPathExpression, public $aliasIdentificationVariable, public $indexBy)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkJoinAssociationDeclaration($this);
    }
}
