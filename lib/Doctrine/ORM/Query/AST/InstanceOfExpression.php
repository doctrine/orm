<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * InstanceOfExpression ::= IdentificationVariable ["NOT"] "INSTANCE" ["OF"] (InstanceOfParameter | "(" InstanceOfParameter {"," InstanceOfParameter}* ")")
 * InstanceOfParameter  ::= AbstractSchemaName | InputParameter
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class InstanceOfExpression extends Node
{
    /**
     * @var bool
     */
    public $not;

    /**
     * @var string
     */
    public $identificationVariable;

    /**
     * @var array
     */
    public $value;

    /**
     * @param string $identVariable
     */
    public function __construct($identVariable)
    {
        $this->identificationVariable = $identVariable;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkInstanceOfExpression($this);
    }
}
