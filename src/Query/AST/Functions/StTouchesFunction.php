<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialRelation;

/**
 * "ST_TOUCHES" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Tests whether two geometries touch each other.
 */
class StTouchesFunction extends SpatialRelationFunction
{
    protected function getRelation(): SpatialRelation
    {
        return SpatialRelation::TOUCHES;
    }
}
