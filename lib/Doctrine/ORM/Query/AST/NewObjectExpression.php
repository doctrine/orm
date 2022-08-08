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
    /** @var string */
    public $className;

    /** @var mixed[] */
    public $args;

    /**
     * @param string  $className
     * @param mixed[] $args
     */
    public function __construct($className, array $args)
    {
        $this->className = $className;
        $this->args      = $args;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkNewObject($this);
    }
}
