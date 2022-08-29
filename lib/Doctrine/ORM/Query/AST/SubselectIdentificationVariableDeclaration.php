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
    /**
     * @param PathExpression $associationPathExpression
     * @param string         $aliasIdentificationVariable
     */
    public function __construct(public $associationPathExpression, public $aliasIdentificationVariable)
    {
    }
}
