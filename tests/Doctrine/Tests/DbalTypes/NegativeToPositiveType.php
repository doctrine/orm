<?php

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class NegativeToPositiveType extends Type
{
    public function getName()
    {
        return 'negative_to_positive';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
    }

    public function canRequireSQLConversion()
    {
        return true;
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return 'ABS(' . $sqlExpr . ')';
    }

    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return '-(' . $sqlExpr . ')';
    }
}
