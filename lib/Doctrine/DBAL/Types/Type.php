<?php

namespace Doctrine\DBAL\Types;

use Doctrine\Common\DoctrineException;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * The base class for so-called Doctrine mapping types.
 * 
 * A Type object is obtained by calling the static {@link getType()} method.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
abstract class Type
{
    /* The following constants represent type codes and mirror the PDO::PARAM_X constants
     * to decouple ourself from PDO.
     */
    const CODE_BOOL = 5;
    const CODE_NULL = 0;
    const CODE_INT = 1;
    const CODE_STR = 2;
    const CODE_LOB = 3;
    
    /** Map of already instantiated type objects. One instance per type (flyweight). */
    private static $_typeObjects = array();
    
    /** The map of supported doctrine mapping types. */
    private static $_typesMap = array(
        'array' => 'Doctrine\DBAL\Types\ArrayType',
        'object' => 'Doctrine\DBAL\Types\ObjectType',
        'boolean' => 'Doctrine\DBAL\Types\BooleanType',
        'integer' => 'Doctrine\DBAL\Types\IntegerType',
        'int' => 'Doctrine\DBAL\Types\IntegerType',
        'smallint' => 'Doctrine\DBAL\Types\SmallIntType',
        'bigint' => 'Doctrine\DBAL\Types\BigIntType',
        'string' => 'Doctrine\DBAL\Types\StringType',
        'text' => 'Doctrine\DBAL\Types\TextType',
        'datetime' => 'Doctrine\DBAL\Types\DateTimeType',
        'date' => 'Doctrine\DBAL\Types\DateType',
        'time' => 'Doctrine\DBAL\Types\TimeType',
        'decimal' => 'Doctrine\DBAL\Types\DecimalType',
        'double' => 'Doctrine\DBAL\Types\DoubleType'
    );
    
    /* Prevent instantiation and force use of the factory method. */
    private function __construct() {}
    
    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     * @return mixed The database representation of the value.
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     * @return mixed The PHP representation of the value.
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }
    
    /**
     * Gets the default length of this type.
     *
     * @todo Needed?
     */
    public function getDefaultLength(AbstractPlatform $platform)
    {
        return null;
    }
    
    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration The field declaration.
     * @param AbstractPlatform $platform The currently used database platform.
     */
    abstract public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform);

    /**
     * Gets the name of this type.
     * 
     * @return string
     * @todo Needed?
     */
    abstract public function getName();

    /**
     * Gets the type code of this type.
     * 
     * @return integer
     */
    public function getTypeCode()
    {
        return self::CODE_STR;
    }
    
    /**
     * Factory method to create type instances.
     * Type instances are implemented as flyweights.
     *
     * @param string $name The name of the type (as returned by getName()).
     * @return Doctrine\DBAL\Types\Type
     */
    public static function getType($name)
    {
        if ( ! isset(self::$_typeObjects[$name])) {
            if ( ! isset(self::$_typesMap[$name])) {
                throw DoctrineException::updateMe("Unknown type: $name");
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
            throw DoctrineException::typeExists($name);
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
            throw DoctrineException::typeNotFound($name);
        }
        self::$_typesMap[$name] = $className;
    }
}