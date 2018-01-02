<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * SubselectIdentificationVariableDeclaration ::= AssociationPathExpression ["AS"] AliasIdentificationVariable
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class SubselectIdentificationVariableDeclaration
{
    /**
     * @var PathExpression
     */
    public $associationPathExpression;

    /**
     * @var string
     */
    public $aliasIdentificationVariable;

    /**
     * Constructor.
     *
     * @param PathExpression $associationPathExpression
     * @param string         $aliasIdentificationVariable
     */
    public function __construct($associationPathExpression, $aliasIdentificationVariable)
    {
        $this->associationPathExpression   = $associationPathExpression;
        $this->aliasIdentificationVariable = $aliasIdentificationVariable;
    }
}
