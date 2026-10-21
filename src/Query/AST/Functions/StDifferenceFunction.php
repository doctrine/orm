<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialSetOperation;

/**
 * "ST_DIFFERENCE" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Returns the part of the first geometry that does not intersect the second one.
 */
class StDifferenceFunction extends SpatialSetOperationFunction
{
    protected function getOperation(): SpatialSetOperation
    {
        return SpatialSetOperation::DIFFERENCE;
    }
}
