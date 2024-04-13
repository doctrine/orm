<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class NegativeToPositiveType extends Type
{
    public const NAME = 'negative_to_positive';

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
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
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
        return 'ABS(' . $sqlExpr . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return '-(' . $sqlExpr . ')';
    }
}
