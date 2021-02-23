<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class UpperCaseStringType extends StringType
{
    public const NAME = 'upper_case_string';

    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function canRequireSQLConversion() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform) : string
    {
        return 'UPPER(' . $sqlExpr . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValueSQL($sqlExpr, $platform) : string
    {
        return 'LOWER(' . $sqlExpr . ')';
    }
}
