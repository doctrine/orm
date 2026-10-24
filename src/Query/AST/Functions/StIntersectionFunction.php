<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialSetOperation;

/**
 * "ST_INTERSECTION" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Returns the part of the first geometry that intersects the second one.
 */
class StIntersectionFunction extends SpatialSetOperationFunction
{
    protected function getOperation(): SpatialSetOperation
    {
        return SpatialSetOperation::INTERSECTION;
    }
}
