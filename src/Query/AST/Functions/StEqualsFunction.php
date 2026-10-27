<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialRelation;

/**
 * "ST_EQUALS" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Tests whether two geometries are spatially equal.
 */
class StEqualsFunction extends SpatialRelationFunction
{
    protected function getRelation(): SpatialRelation
    {
        return SpatialRelation::EQUALS;
    }
}
