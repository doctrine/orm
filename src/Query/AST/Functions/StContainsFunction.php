<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialRelation;

/**
 * "ST_CONTAINS" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * Tests whether the first geometry contains the second one.
 */
class StContainsFunction extends SpatialRelationFunction
{
    protected function getRelation(): SpatialRelation
    {
        return SpatialRelation::CONTAINS;
    }
}
