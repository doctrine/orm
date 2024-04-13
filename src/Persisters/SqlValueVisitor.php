<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

/**
 * Extract the values from a criteria/expression
 */
class SqlValueVisitor extends ExpressionVisitor
{
    /** @var mixed[] */
    private $values = [];

    /** @var mixed[][] */
    private $types = [];

    /**
     * Converts a comparison expression into the target query language output.
     *
     * {@inheritDoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        $value = $this->getValueFromComparison($comparison);

        $this->values[] = $value;
        $this->types[]  = [$comparison->getField(), $value, $comparison->getOperator()];

        return null;
    }

    /**
     * Converts a composite expression into the target query language output.
     *
     * {@inheritDoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        foreach ($expr->getExpressionList() as $child) {
            $this->dispatch($child);
        }

        return null;
    }

    /**
     * Converts a value expression into the target query language part.
     *
     * {@inheritDoc}
     */
    public function walkValue(Value $value)
    {
        return null;
    }

    /**
     * Returns the Parameters and Types necessary for matching the last visited expression.
     *
     * @return mixed[][]
     * @psalm-return array{0: array, 1: array<array<mixed>>}
     */
    public function getParamsAndTypes()
    {
        return [$this->values, $this->types];
    }

    /**
     * Returns the value from a Comparison. In case of a CONTAINS comparison,
     * the value is wrapped in %-signs, because it will be used in a LIKE clause.
     *
     * @return mixed
     */
    protected function getValueFromComparison(Comparison $comparison)
    {
        $value = $comparison->getValue()->getValue();

        switch ($comparison->getOperator()) {
            case Comparison::CONTAINS:
                return '%' . $value . '%';

            case Comparison::STARTS_WITH:
                return $value . '%';

            case Comparison::ENDS_WITH:
                return '%' . $value;

            default:
                return $value;
        }
    }
}
