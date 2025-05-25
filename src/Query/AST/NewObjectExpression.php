<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

use function func_get_arg;
use function func_num_args;

/**
 * NewObjectExpression ::= "NEW" IdentificationVariable "(" NewObjectArg {"," NewObjectArg}* ")"
 *
 * @link    www.doctrine-project.org
 */
class NewObjectExpression extends Node
{
    public bool $hasNamedArgs = false;

    /**
     * @param class-string $className
     * @param mixed[]      $args
     */
    public function __construct(public string $className, public array $args /*, public bool $hasNamedArgs = false */)
    {
        if (func_num_args() > 2) {
            $this->hasNamedArgs = func_get_arg(2);
        }
    }

    public function dispatch(SqlWalker $walker /*, string|null $parentAlias = null */): string
    {
        $parentAlias = func_num_args() > 1 ? func_get_arg(1) : null;

        return $walker->walkNewObject($this, $parentAlias);
    }
}
