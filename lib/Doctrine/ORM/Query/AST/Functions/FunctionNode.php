<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Abstract Function Node.
 *
 * @link    www.doctrine-project.org
 *
 * @psalm-consistent-constructor
 */
abstract class FunctionNode extends Node
{
    public function __construct(public string $name)
    {
    }

    abstract public function getSql(SqlWalker $sqlWalker): string;

    public function dispatch(SqlWalker $sqlWalker): string
    {
        return $sqlWalker->walkFunction($this);
    }

    abstract public function parse(Parser $parser): void;
}
