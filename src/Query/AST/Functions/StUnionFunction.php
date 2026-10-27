<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialSetOperation;

/**
 * "ST_UNION" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Returns the union of two geometries.
 */
class StUnionFunction extends SpatialSetOperationFunction
{
    protected function getOperation(): SpatialSetOperation
    {
        return SpatialSetOperation::UNION;
    }
}
