<?php

/**
 * Type that maps an SQL INT to a PHP integer.
 *
 */
class Doctrine_DBAL_Types_IntegerType extends Doctrine_DBAL_Types_Type
{

    public function getName() { return "Integer"; }

    public function getSqlDeclaration(array $fieldDeclaration, Doctrine_DBAL_Platforms_AbstractPlatform $platform)
    {
        return $platform->getIntegerTypeDeclarationSql($fieldDeclaration);
    }

    public function convertToPHPValue($value)
    {
        return (int)$value;
    }
}

