<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialRelation;

/**
 * "ST_CROSSES" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Tests whether two geometries cross each other.
 */
class StCrossesFunction extends SpatialRelationFunction
{
    protected function getRelation(): SpatialRelation
    {
        return SpatialRelation::CROSSES;
    }
}
