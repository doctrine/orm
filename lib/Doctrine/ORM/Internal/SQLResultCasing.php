<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal;

use Doctrine\DBAL\Platforms\AbstractPlatform;

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
        switch ($platform->getName()) {
            case 'db2':
            case 'oracle':
                return strtoupper($column);

            case 'postgresql':
                return strtolower($column);
        }

        if (method_exists(AbstractPlatform::class, 'getSQLResultCasing')) {
            return $platform->getSQLResultCasing($column);
        }

        return $column;
    }
}
