<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * AssociationPathExpression ::= CollectionValuedPathExpression | SingleValuedAssociationPathExpression
 * SingleValuedPathExpression ::= StateFieldPathExpression | SingleValuedAssociationPathExpression
 * StateFieldPathExpression ::= SimpleStateFieldPathExpression | SimpleStateFieldAssociationPathExpression
 * SingleValuedAssociationPathExpression ::= IdentificationVariable "." SingleValuedAssociationField
 * CollectionValuedPathExpression ::= IdentificationVariable "." CollectionValuedAssociationField
 * StateField ::= {EmbeddedClassStateField "."}* SimpleStateField
 * SimpleStateFieldPathExpression ::= IdentificationVariable "." StateField
 */
class PathExpression extends Node
{
    public const TYPE_COLLECTION_VALUED_ASSOCIATION = 2;
    public const TYPE_SINGLE_VALUED_ASSOCIATION     = 4;
    public const TYPE_STATE_FIELD                   = 8;

    /**
     * @var int|null
     * @psalm-var self::TYPE_*|null
     */
    public $type;

    /**
     * @var int
     * @psalm-var int-mask-of<self::TYPE_*>
     */
    public $expectedType;

    /** @var string */
    public $identificationVariable;

    /** @var string|null */
    public $field;

    /**
     * @param int         $expectedType
     * @param string      $identificationVariable
     * @param string|null $field
     * @psalm-param int-mask-of<self::TYPE_*> $expectedType
     */
    public function __construct($expectedType, $identificationVariable, $field = null)
    {
        $this->expectedType           = $expectedType;
        $this->identificationVariable = $identificationVariable;
        $this->field                  = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkPathExpression($this);
    }
}
