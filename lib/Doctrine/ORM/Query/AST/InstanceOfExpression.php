<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Query\SqlWalker;

use function func_num_args;

/**
 * InstanceOfExpression ::= IdentificationVariable ["NOT"] "INSTANCE" ["OF"] (InstanceOfParameter | "(" InstanceOfParameter {"," InstanceOfParameter}* ")")
 * InstanceOfParameter  ::= AbstractSchemaName | InputParameter
 *
 * @link    www.doctrine-project.org
 */
class InstanceOfExpression extends Node
{
    /**
     * @param string                                $identVariable
     * @param non-empty-list<InputParameter|string> $value
     */
    public function __construct(
        public $identificationvariableVariable,
        public array $value = [],
        public bool $not = false,
    ) {
        if (func_num_args() < 2) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/10267',
                'Not passing a value for $value to %s() is deprecated.',
                __METHOD__,
            );
        }

        $this->identificationVariable = $identVariable;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkInstanceOfExpression($this);
    }
}
