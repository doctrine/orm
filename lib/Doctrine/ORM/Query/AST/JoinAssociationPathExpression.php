<?php

namespace Doctrine\ORM\Query\AST;

/**
 * JoinAssociationPathExpression ::= IdentificationVariable "." (SingleValuedAssociationField | CollectionValuedAssociationField)
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Roman Borschel
 */
class JoinAssociationPathExpression extends Node
{
    private $_identificationVariable;
    private $_assocField;

    public function __construct($identificationVariable, $assocField)
    {
        $this->_identificationVariable = $identificationVariable;
        $this->_assocField = $assocField;
    }

    public function getIdentificationVariable()
    {
        return $this->_identificationVariable;
    }
    
    public function getAssociationField()
    {
        return $this->_assocField;
    }

    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkJoinPathExpression($this);
    }
}