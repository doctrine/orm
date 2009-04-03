<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps a database SMALLINT to a PHP integer.
 *
 * @author robo
 */
class SmallIntType
{
    public function getName()
    {
        return "SmallInteger";
    }

    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getSmallIntTypeDeclarationSql($fieldDeclaration);
    }

    public function convertToPHPValue($value)
    {
        return (int) $value;
    }
}