<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

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

    /**
     * @param int         $expectedType
     * @param string      $identificationVariable
     * @param string|null $field
     * @psalm-param int-mask-of<self::TYPE_*> $expectedType
     */
    public function __construct($expectedType, public $identificationVariable, public $field = null)
    {
        $this->expectedType = $expectedType;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkPathExpression($this);
    }
}
