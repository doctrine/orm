<?php

#namespace Doctrine::DBAL::Types;

abstract class Doctrine_DataType
{
    private static $_typeObjects = array();
    private static $_typesMap = array(
        'integer' => 'Doctrine_DataType_IntegerType',
        'string' => 'Doctrine_DataType_StringType',
        'text' => 'Doctrine_DataType_TextType',
        'datetime' => 'Doctrine_DataType_DateTimeType',
        'decimal' => 'Doctrine_DataType_DecimalType',
        'double' => 'Doctrine_DataType_DoubleType'
    );
    
    public function convertToDatabaseValue($value, Doctrine_DatabasePlatform $platform)
    {
        return $value;
    }
    
    public function convertToObjectValue($value)
    {
        return $value;
    }
    
    abstract public function getDefaultLength(Doctrine_DatabasePlatform $platform);
    abstract public function getSqlDeclaration(array $fieldDeclaration, Doctrine_DatabasePlatform $platform);
    abstract public function getName();
    
    /**
     * Factory method.
     *
     * @param string $name The name of the type (as returned by getName()).
     * @return Doctrine::DBAL::Types::Type
     */
    public static function getType($name)
    {
        if ( ! isset(self::$_typeObjects[$name])) {
            if ( ! isset(self::$_typesMap[$name])) {
                throw Doctrine_Exception::unknownType($name);
            }
            self::$_typeObjects[$name] = new self::$_typesMap[$name]();
        }
        return self::$_typeObjects[$name];
    }
    
    /**
     * Adds a custom type to the type map.
     *
     * @param string $name Name of the type. This should correspond to what
     *                           getName() returns.
     * @param string $className The class name of the custom type.
     */
    public static function addCustomType($name, $className)
    {
        if (isset(self::$_typesMap[$name])) {
            throw Doctrine_Exception::typeExists($name);
        }
        self::$_typesMap[$name] = $className;
    }
    
    /**
     * Overrides an already defined type to use a different implementation.
     *
     * @param string $name
     * @param string $className
     */
    public static function overrideType($name, $className)
    {
        if ( ! isset(self::$_typesMap[$name])) {
            throw Doctrine_Exception::typeNotFound($name);
        }
        self::$_typesMap[$name] = $className;
    }
}

?>