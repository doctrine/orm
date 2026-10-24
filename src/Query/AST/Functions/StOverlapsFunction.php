<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialRelation;

/**
 * "ST_OVERLAPS" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Tests whether two geometries overlap each other.
 */
class StOverlapsFunction extends SpatialRelationFunction
{
    protected function getRelation(): SpatialRelation
    {
        return SpatialRelation::OVERLAPS;
    }
}
