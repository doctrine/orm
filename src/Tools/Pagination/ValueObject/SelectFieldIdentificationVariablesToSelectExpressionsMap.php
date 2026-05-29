<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination\ValueObject;

use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;

use function is_string;

/** @internal */
final class SelectFieldIdentificationVariablesToSelectExpressionsMap
{
    /** @var array<string, SelectExpression> */
    private array $map;

    public function __construct(SelectStatement $selectStatement)
    {
        foreach ($selectStatement->selectClause->selectExpressions as $selectExpression) {
            if (
                ! $selectExpression instanceof SelectExpression
                || ! is_string($selectExpression->fieldIdentificationVariable)
            ) {
                continue;
            }

            $this->map[$selectExpression->fieldIdentificationVariable] = $selectExpression;
        }
    }

    public function getSelectExpression(string $fieldIdentificationVariable): SelectExpression|null
    {
        return $this->map[$fieldIdentificationVariable] ?? null;
    }
}
