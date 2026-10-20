<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Spatial\SpatialRelation;
use Doctrine\ORM\Query\Parser;

use function enum_exists;
use function sprintf;
use function strtoupper;

/** @internal */
final class SpatialFunctions
{
    /**
     * Fails if the installed version of DBAL does not generate the SQL of spatial functions.
     */
    public static function assertSupported(Parser $parser, string $name): void
    {
        if (enum_exists(SpatialRelation::class)) {
            return;
        }

        $parser->semanticalError(sprintf('%s() requires doctrine/dbal 4.6 or newer.', strtoupper($name)));
    }

    private function __construct()
    {
    }
}
