<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps a PHP object to a clob SQL type.
 *
 * @since 2.0
 */
class ObjectType extends Type
{
    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return serialize($value);
    }

    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        $value = (is_resource($value)) ? stream_get_contents($value) : $value;
        return unserialize($value);
    }

    public function getName()
    {
        return Type::OBJECT;
    }
}