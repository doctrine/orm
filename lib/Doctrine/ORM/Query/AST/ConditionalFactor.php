<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * ConditionalFactor ::= ["NOT"] ConditionalPrimary
 *
 * @link    www.doctrine-project.org
 */
class ConditionalFactor extends Node
{
    /** @param ConditionalPrimary $conditionalPrimary */
    public function __construct(
        public $conditionalPrimary,
        public bool $not = false,
    ) {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkConditionalFactor($this);
    }
}
