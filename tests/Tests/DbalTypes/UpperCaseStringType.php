<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class UpperCaseStringType extends StringType
{
    public const NAME = 'upper_case_string';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function canRequireSQLConversion()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return 'UPPER(' . $sqlExpr . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return 'LOWER(' . $sqlExpr . ')';
    }
}
