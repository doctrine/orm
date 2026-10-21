<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialSetOperation;

/**
 * "ST_SYMDIFFERENCE" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Returns the parts of the geometries that do not intersect each other.
 */
class StSymDifferenceFunction extends SpatialSetOperationFunction
{
    protected function getOperation(): SpatialSetOperation
    {
        return SpatialSetOperation::SYMMETRIC_DIFFERENCE;
    }
}
