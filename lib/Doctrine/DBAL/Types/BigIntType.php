<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps a database BIGINT to a PHP string.
 *
 * @author robo
 * @since 2.0
 */
class BigIntType extends Type
{
    public function getName()
    {
        return 'BigInteger';
    }

    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getBigIntTypeDeclarationSQL($fieldDeclaration);
    }
    
    public function getTypeCode()
    {
    	return self::CODE_INT;
    }
}