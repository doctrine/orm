<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps a database SMALLINT to a PHP integer.
 *
 * @author robo
 */
class SmallIntType extends Type
{
    public function getName()
    {
        return "SmallInteger";
    }

    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getSmallIntTypeDeclarationSql($fieldDeclaration);
    }

    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return (int) $value;
    }
    
    public function getTypeCode()
    {
    	return self::CODE_INT;
    }
}