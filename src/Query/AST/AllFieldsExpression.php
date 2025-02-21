<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * AllFieldsExpression ::= u.*
 *
 * @link    www.doctrine-project.org
 */
class AllFieldsExpression extends Node
{
    public string $field = '';

    public function __construct(
        public string|null $identificationVariable,
    ) {
        $this->field = $this->identificationVariable . '.*';
    }

    public function dispatch(SqlWalker $walker, int|string $parent = '', int|string $argIndex = '', int|null &$aliasGap = null): string
    {
        return $walker->walkAllEntityFieldsExpression($this, $parent, $argIndex, $aliasGap);
    }
}
