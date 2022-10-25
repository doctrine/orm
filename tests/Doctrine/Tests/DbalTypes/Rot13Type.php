<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

use function str_rot13;

/**
 * Shifts every letter by 13 places in the alphabet (ROT13 encoding).
 */
class Rot13Type extends Type
{
    /**
     * {@inheritdoc}
     *
     * @param string|null $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        return str_rot13($value);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|null $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        return str_rot13($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function getName(): string
    {
        return 'rot13';
    }
}
