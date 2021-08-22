<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;

use function method_exists;
use function strtolower;
use function strtoupper;

/**
 * @internal
 */
trait SQLResultCasing
{
    private function getSQLResultCasing(AbstractPlatform $platform, string $column): string
    {
        if ($platform instanceof DB2Platform || $platform instanceof OraclePlatform) {
            return strtoupper($column);
        }

        if ($platform instanceof PostgreSQL94Platform || $platform instanceof PostgreSQLPlatform) {
            return strtolower($column);
        }

        if (method_exists(AbstractPlatform::class, 'getSQLResultCasing')) {
            return $platform->getSQLResultCasing($column);
        }

        return $column;
    }
}
