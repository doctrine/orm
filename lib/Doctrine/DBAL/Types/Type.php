<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform,
    Doctrine\DBAL\DBALException;

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
    const TARRAY = 'array';
    const BIGINT = 'bigint';
    const BOOLEAN = 'boolean';
    const DATETIME = 'datetime';
    const DATE = 'date';
    const TIME = 'time';
    const DECIMAL = 'decimal';
    const INTEGER = 'integer';
    const OBJECT = 'object';
    const SMALLINT = 'smallint';
    const STRING = 'string';
    const TEXT = 'text';

    /** Map of already instantiated type objects. One instance per type (flyweight). */
    private static $_typeObjects = array();

    /** The map of supported doctrine mapping types. */
    private static $_typesMap = array(
        self::TARRAY => 'Doctrine\DBAL\Types\ArrayType',
        self::OBJECT => 'Doctrine\DBAL\Types\ObjectType',
        self::BOOLEAN => 'Doctrine\DBAL\Types\BooleanType',
        self::INTEGER => 'Doctrine\DBAL\Types\IntegerType',
        self::SMALLINT => 'Doctrine\DBAL\Types\SmallIntType',
        self::BIGINT => 'Doctrine\DBAL\Types\BigIntType',
        self::STRING => 'Doctrine\DBAL\Types\StringType',
        self::TEXT => 'Doctrine\DBAL\Types\TextType',
        self::DATETIME => 'Doctrine\DBAL\Types\DateTimeType',
        self::DATE => 'Doctrine\DBAL\Types\DateType',
        self::TIME => 'Doctrine\DBAL\Types\TimeType',
        self::DECIMAL => 'Doctrine\DBAL\Types\DecimalType'
    );

    /* Prevent instantiation and force use of the factory method. */
    final private function __construct() {}

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
     * Factory method to create type instances.
     * Type instances are implemented as flyweights.
     *
     * @static
     * @throws DBALException
     * @param string $name The name of the type (as returned by getName()).
     * @return Doctrine\DBAL\Types\Type
     */
    public static function getType($name)
    {
        if ( ! isset(self::$_typeObjects[$name])) {
            if ( ! isset(self::$_typesMap[$name])) {
                throw DBALException::unknownColumnType($name);
            }
            self::$_typeObjects[$name] = new self::$_typesMap[$name]();
        }

        return self::$_typeObjects[$name];
    }

    /**
     * Adds a custom type to the type map.
     *
     * @static
     * @param string $name Name of the type. This should correspond to what getName() returns.
     * @param string $className The class name of the custom type.
     * @throws DBALException
     */
    public static function addType($name, $className)
    {
        if (isset(self::$_typesMap[$name])) {
            throw DBALException::typeExists($name);
        }

        self::$_typesMap[$name] = $className;
    }

    /**
     * Checks if exists support for a type.
     *
     * @static
     * @param string $name Name of the type
     * @return boolean TRUE if type is supported; FALSE otherwise
     */
    public static function hasType($name)
    {
        return isset(self::$_typesMap[$name]);
    }

    /**
     * Overrides an already defined type to use a different implementation.
     *
     * @static
     * @param string $name
     * @param string $className
     * @throws DBALException
     */
    public static function overrideType($name, $className)
    {
        if ( ! isset(self::$_typesMap[$name])) {
            throw DBALException::typeNotFound($name);
        }

        self::$_typesMap[$name] = $className;
    }

    /**
     * Gets the (preferred) binding type for values of this type that
     * can be used when binding parameters to prepared statements.
     * 
     * This method should return one of the PDO::PARAM_* constants, that is, one of:
     * 
     * PDO::PARAM_BOOL
     * PDO::PARAM_NULL
     * PDO::PARAM_INT
     * PDO::PARAM_STR
     * PDO::PARAM_LOB
     * 
     * @return integer
     */
    public function getBindingType()
    {
        return \PDO::PARAM_STR;
    }

    /**
     * Get the types array map which holds all registered types and the corresponding
     * type class
     *
     * @return array $typesMap
     */
    public static function getTypesMap()
    {
        return self::$_typesMap;
    }

    public function __toString()
    {
        $e = explode('\\', get_class($this));
        return str_replace('Type', '', end($e));
    }
}