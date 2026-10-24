<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialRelation;

/**
 * "ST_WITHIN" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Tests whether the first geometry is within the second one.
 */
class StWithinFunction extends SpatialRelationFunction
{
    protected function getRelation(): SpatialRelation
    {
        return SpatialRelation::WITHIN;
    }
}
