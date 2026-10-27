<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialRelation;

/**
 * "ST_INTERSECTS" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Tests whether two geometries intersect.
 */
class StIntersectsFunction extends SpatialRelationFunction
{
    protected function getRelation(): SpatialRelation
    {
        return SpatialRelation::INTERSECTS;
    }
}
