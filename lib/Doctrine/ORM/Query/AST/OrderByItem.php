<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

use function strtoupper;

/**
 * OrderByItem ::= (ResultVariable | StateFieldPathExpression) ["ASC" | "DESC"]
 *
 * @link    www.doctrine-project.org
 */
class OrderByItem extends Node
{
    /** @var string */
    public $type;

    /** @param mixed $expression */
    public function __construct(public $expression)
    {
    }

    /** @return bool */
    public function isAsc()
    {
        return strtoupper($this->type) === 'ASC';
    }

    /** @return bool */
    public function isDesc()
    {
        return strtoupper($this->type) === 'DESC';
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkOrderByItem($this);
    }
}
