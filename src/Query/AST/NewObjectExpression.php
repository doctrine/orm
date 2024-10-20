<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * NewObjectExpression ::= "NEW" IdentificationVariable "(" NewObjectArg {"," NewObjectArg}* ")"
 *
 * @link    www.doctrine-project.org
 */
class NewObjectExpression extends Node
{
    /** @param mixed[] $args */
    public function __construct(public string $className, public array $args)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkNewObject($this);
    }
}
