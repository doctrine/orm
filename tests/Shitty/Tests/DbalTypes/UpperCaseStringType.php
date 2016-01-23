<?php

namespace Shitty\Tests\DbalTypes;

use Shitty\DBAL\Types\StringType;
use Shitty\DBAL\Platforms\AbstractPlatform;

class UpperCaseStringType extends StringType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'upper_case_string';
    }

    /**
     * {@inheritdoc}
     */
    public function canRequireSQLConversion()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return 'UPPER(' . $sqlExpr . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return 'LOWER(' . $sqlExpr . ')';
    }
}
