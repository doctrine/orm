<?php

namespace Doctrine\ORM\Query\AST;

/**
 * AST node for the following path expressions:
 * 
 * AssociationPathExpression ::= CollectionValuedPathExpression | SingleValuedAssociationPathExpression
 *
 * SingleValuedPathExpression ::= StateFieldPathExpression | SingleValuedAssociationPathExpression
 *
 * StateFieldPathExpression ::= SimpleStateFieldPathExpression | SimpleStateFieldAssociationPathExpression
 *
 * SingleValuedAssociationPathExpression ::= IdentificationVariable "." {SingleValuedAssociationField "."}* SingleValuedAssociationField
 *
 * CollectionValuedPathExpression ::= IdentificationVariable "." {SingleValuedAssociationField "."}* CollectionValuedAssociationField
 * 
 * StateField ::= {EmbeddedClassStateField "."}* SimpleStateField
 *
 * SimpleStateFieldPathExpression ::= IdentificationVariable "." StateField
 * 
 * SimpleStateFieldAssociationPathExpression ::= SingleValuedAssociationPathExpression "." StateField
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class PathExpression extends Node
{
    const TYPE_SINGLE_VALUED_PATH_EXPRESSION = 1;
    const TYPE_COLLECTION_VALUED_ASSOCIATION = 2;
    const TYPE_SINGLE_VALUED_ASSOCIATION = 4;
    const TYPE_STATE_FIELD = 8;
    
    private $_type;
    private $_identificationVariable;
    private $_parts;
    
    public function __construct($type, $identificationVariable, array $parts)
    {
        $this->_type = $type;
        $this->_identificationVariable = $identificationVariable;
        $this->_parts = $parts;
    }
    
    public function getIdentificationVariable()
    {
        return $this->_identificationVariable;
    }
    
    public function getParts()
    {
        return $this->_parts;
    }
    
    public function setType($type)
    {
        $this->_type = $type;
    }
    
    public function getType()
    {
        return $this->_type;
    }
    
    public function dispatch($walker)
    {
        switch ($this->_type) {
            case self::TYPE_STATE_FIELD:
                return $walker->walkStateFieldPathExpression($this);
            case self::TYPE_SINGLE_VALUED_ASSOCIATION:
                return $walker->walkSingleValuedAssociationPathExpression($this);
            case self::TYPE_COLLECTION_VALUED_ASSOCIATION:
                return $walker->walkCollectionValuedAssociationPathExpression($this);
            default:
                throw new \Exception("Unexhaustive match.");
        }
    }
}
