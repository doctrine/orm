<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\Type;

use function addcslashes;
use function array_map;
use function implode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Stores lists of strings as the arrays that ARRAYS_OVERLAP() compares: PostgreSQL arrays, and JSON arrays on the other
 * platforms.
 */
class StringArrayType extends Type
{
    public const NAME = 'string_array';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform instanceof PostgreSQLPlatform) {
            return 'TEXT[]';
        }

        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        if ($platform instanceof PostgreSQLPlatform) {
            return '{' . implode(',', array_map(
                static fn (string $element): string => '"' . addcslashes($element, '"\\') . '"',
                $value,
            )) . '}';
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
