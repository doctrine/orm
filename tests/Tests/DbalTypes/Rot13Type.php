<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

use function method_exists;
use function str_rot13;

/**
 * Shifts every letter by 13 places in the alphabet (ROT13 encoding).
 */
class Rot13Type extends Type
{
    /**
     * {@inheritDoc}
     *
     * @param string|null      $value
     * @param AbstractPlatform $platform
     *
     * @return string|null
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        return str_rot13($value);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|null      $value
     * @param AbstractPlatform $platform
     *
     * @return string|null
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        return str_rot13($value);
    }

    /**
     * {@inheritDoc}
     *
     * @param array            $fieldDeclaration
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if (method_exists($platform, 'getStringTypeDeclarationSQL')) {
            return $platform->getStringTypeDeclarationSQL($fieldDeclaration);
        }

        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritDoc}
     *
     * @param AbstractPlatform $platform
     *
     * @return int|null
     */
    public function getDefaultLength(AbstractPlatform $platform)
    {
        return $platform->getVarcharDefaultLength();
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'rot13';
    }
}
