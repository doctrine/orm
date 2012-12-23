<?php

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

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
