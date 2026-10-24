<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialRelation;

/**
 * "ST_DISJOINT" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Tests whether two geometries are disjoint.
 */
class StDisjointFunction extends SpatialRelationFunction
{
    protected function getRelation(): SpatialRelation
    {
        return SpatialRelation::DISJOINT;
    }
}
