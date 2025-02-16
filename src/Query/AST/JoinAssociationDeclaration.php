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
    public function __construct(
        public JoinAssociationPathExpression $joinAssociationPathExpression,
        public string $aliasIdentificationVariable,
        public IndexBy|null $indexBy,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkJoinAssociationDeclaration($this);
    }
}
