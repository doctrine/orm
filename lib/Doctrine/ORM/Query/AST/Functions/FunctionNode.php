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
    /** @param string $name */
    public function __construct(public $name)
    {
    }

    /** @return string */
    abstract public function getSql(SqlWalker $sqlWalker);

    public function dispatch(SqlWalker $sqlWalker): string
    {
        return $sqlWalker->walkFunction($this);
    }

    /** @return void */
    abstract public function parse(Parser $parser);
}
