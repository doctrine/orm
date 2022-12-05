<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * InstanceOfExpression ::= IdentificationVariable ["NOT"] "INSTANCE" ["OF"] (InstanceOfParameter | "(" InstanceOfParameter {"," InstanceOfParameter}* ")")
 * InstanceOfParameter  ::= AbstractSchemaName | InputParameter
 *
 * @link    www.doctrine-project.org
 */
class InstanceOfExpression extends Node
{
    /**
     * @param string                                $identificationVariable
     * @param non-empty-list<InputParameter|string> $value
     */
    public function __construct(
        public $identificationVariable,
        public array $value,
        public bool $not = false,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkInstanceOfExpression($this);
    }
}
