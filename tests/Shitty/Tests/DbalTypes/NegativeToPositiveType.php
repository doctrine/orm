<?php

namespace Shitty\Tests\DbalTypes;

use Shitty\DBAL\Types\Type;
use Shitty\DBAL\Platforms\AbstractPlatform;

class NegativeToPositiveType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'negative_to_positive';
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
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
        return 'ABS(' . $sqlExpr . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return '-(' . $sqlExpr . ')';
    }
}
