<?php

/**
 * This file is part of the doctrine2 package.
 * doctrine2 is licensed under ${PROJECT_LICENSE_TYPE} (${PROJECT_LICENSE_URL}).
 */

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class UuidType extends Type
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace('-', '', $value);
        $value = hex2bin($value);

        return $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $value = bin2hex($value);
        $value = substr_replace($value, '-', 20, 0);
        $value = substr_replace($value, '-', 16, 0);
        $value = substr_replace($value, '-', 12, 0);
        $value = substr_replace($value, '-', 8, 0);

        return $value;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'BINARY(16)';
    }

    public function getName()
    {
        return 'uuid';
    }

    public function getBindingType()
    {
        return \PDO::PARAM_LOB;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
