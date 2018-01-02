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
 *
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class PathExpression extends Node
{
    const TYPE_COLLECTION_VALUED_ASSOCIATION = 2;
    const TYPE_SINGLE_VALUED_ASSOCIATION = 4;
    const TYPE_STATE_FIELD = 8;

    /**
     * @var int
     */
    public $type;

    /**
     * @var int
     */
    public $expectedType;

    /**
     * @var string
     */
    public $identificationVariable;

    /**
     * @var string|null
     */
    public $field;

    /**
     * @param int         $expectedType
     * @param string      $identificationVariable
     * @param string|null $field
     */
    public function __construct($expectedType, $identificationVariable, $field = null)
    {
        $this->expectedType = $expectedType;
        $this->identificationVariable = $identificationVariable;
        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($walker)
    {
        return $walker->walkPathExpression($this);
    }
}
