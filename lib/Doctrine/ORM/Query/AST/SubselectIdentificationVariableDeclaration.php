<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * SubselectIdentificationVariableDeclaration ::= AssociationPathExpression ["AS"] AliasIdentificationVariable
 *
 * @link    www.doctrine-project.org
 */
class SubselectIdentificationVariableDeclaration
{
    public function __construct(
        public PathExpression $associationPathExpression,
        public string $aliasIdentificationVariable,
    ) {
    }
}
