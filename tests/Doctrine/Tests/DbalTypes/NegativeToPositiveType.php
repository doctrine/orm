<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class NegativeToPositiveType extends Type
{
    public const NAME = 'negative_to_positive';

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
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) : string
    {
        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
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
        return 'ABS(' . $sqlExpr . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValueSQL($sqlExpr, $platform) : string
    {
        return '-(' . $sqlExpr . ')';
    }
}
