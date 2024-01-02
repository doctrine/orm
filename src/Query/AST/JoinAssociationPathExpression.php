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
    public function __construct(
        public string $identificationVariable,
        public string $associationField,
    ) {
    }
}
