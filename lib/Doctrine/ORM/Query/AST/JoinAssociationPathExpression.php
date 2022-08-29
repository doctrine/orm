<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * JoinAssociationPathExpression ::= IdentificationVariable "." (SingleValuedAssociationField | CollectionValuedAssociationField)
 *
 * @link    www.doctrine-project.org
 */
class JoinAssociationPathExpression extends Node
{
    /**
     * @param string $identificationVariable
     * @param string $associationField
     */
    public function __construct(public $identificationVariable, public $associationField)
    {
    }
}
