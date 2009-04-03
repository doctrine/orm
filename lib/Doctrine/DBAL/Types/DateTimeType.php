<?php

namespace Doctrine\DBAL\Types;

/**
 * Type that maps an SQL DATETIME to a PHP DateTime object.
 *
 * @since 2.0
 */
class DateTimeType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getDateTimeTypeDeclarationSql($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function convertToDatabaseValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        //TODO: howto? dbms specific? delegate to platform?
        return $value;
    }
    
    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function convertToObjectValue($value)
    {
        return new \DateTime($value);
    }
}