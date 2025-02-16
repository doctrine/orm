<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use Stringable;

/**
 * Expression class for DQL comparison expressions.
 *
 * @link    www.doctrine-project.org
 */
class Comparison implements Stringable
{
    final public const EQ  = '=';
    final public const NEQ = '<>';
    final public const LT  = '<';
    final public const LTE = '<=';
    final public const GT  = '>';
    final public const GTE = '>=';

    /** Creates a comparison expression with the given arguments. */
    public function __construct(protected mixed $leftExpr, protected string $operator, protected mixed $rightExpr)
    {
    }

    public function getLeftExpr(): mixed
    {
        return $this->leftExpr;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getRightExpr(): mixed
    {
        return $this->rightExpr;
    }

    public function __toString(): string
    {
        return $this->leftExpr . ' ' . $this->operator . ' ' . $this->rightExpr;
    }
}
