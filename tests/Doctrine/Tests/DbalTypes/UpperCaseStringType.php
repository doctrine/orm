<?php

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class UpperCaseStringType extends StringType
{
    public function getName()
    {
        return 'upper_case_string';
    }

    public function canRequireSQLConversion()
    {
        return true;
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return 'UPPER(' . $sqlExpr . ')';
    }

    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return 'LOWER(' . $sqlExpr . ')';
    }
}
