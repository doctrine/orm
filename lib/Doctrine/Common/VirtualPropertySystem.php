<?php
/**
 * The VirtualPropertySystem class is a class consisting solely of static methods and
 * serves as a generic virtual property registry system.
 * Classes register their (virtual) properties with the property system, optionally specifying
 * property features/options. These can then be evaluated by other code.
 *
 * @author robo
 * @since 2.0
 */
class Doctrine_Common_VirtualPropertySystem {
    private static $_properties = array();
    private static $_callback = 'construct';
    private static $_checkTypes = false;
    private static $_useAutoAccessorOverride = true;
    private static $_simplePHPTypes = array(
        'int' => true,
        'string' => true,
        'bool' => true,
        'double' => true
    );

    /** Private constructor. This class cannot be instantiated. */
    private function __construct() {}

    /**
     * Gets all properties of a class that are registered with the VirtualPropertySystem.
     *
     * @param string $class
     * @return array
     */
    public static function getProperties($class)
    {
        if ( ! self::isInitialized($class)) {
            self::initialize($class);
        }
        return self::$_properties[$class];
    }

    /**
     * Gets whether automatic accessor overrides are enabled.
     *
     * @return boolean
     */
    public static function isAutoAccessorOverride()
    {
        return self::$_useAutoAccessorOverride;
    }

    /**
     * Sets whether automatic accessor overrides are enabled.
     *
     * @param boolean $bool
     */
    public static function setAutoAccessorOverride($bool)
    {
        self::$_useAutoAccessorOverride = (bool)$bool;
    }

    /**
     * Prepopulates the property system.
     *
     * @param array $properties
     */
    public static function populate(array $properties)
    {
        self::$_properties = $properties;
    }

    /**
     * Checks whether the given type is a simple PHP type.
     * Simple php types are: int, string, bool, double.
     *
     * @param string $type The type to check.
     * @return boolean
     */
    public static function isSimplePHPType($type)
    {
        return isset(self::$_simplePHPTypes[$type]);
    }

    /**
     * Gets whether type checks are enabled.
     *
     * @return boolean
     */
    public static function isTypeCheckEnabled()
    {
        return self::$_checkTypes;
    }

    /**
     * Sets whether type checks are enabled.
     *
     * @param boolean $bool
     */
    public static function setTypeCheckEnabled($bool)
    {
        self::$_checkTypes = (bool)$bool;
    }

    /**
     * Sets the name of the callback method to use for initializing the virtual
     * properties of a class. The callback must be static and public.
     *
     * @param string $callback
     */
    public static function setCallback($callback)
    {
        self::$_callback = $callback;
    }

    /**
     * Registers a virtual property for a class.
     *
     * @param string $class
     * @param string $propName
     * @param string $type
     * @param string $accessor
     * @param string $mutator
     */
    public static function register($class, $propName, $type, $accessor = null, $mutator = null)
    {
        self::$_properties[$class][$propName] = array(
                'type' => $type, 'accessor' => $accessor, 'mutator' => $mutator
                );
    }

    /**
     * Gets whether a class has already been initialized by the virtual property system.
     *
     * @param string $class
     * @return boolean
     */
    public static function isInitialized($class)
    {
        return isset(self::$_properties[$class]);
    }

    /**
     * Initializes a class with the virtual property system.
     *
     * @param <type> $class
     */
    public static function initialize($class)
    {
        if (method_exists($class, self::$_callback)) {
            call_user_func(array($class, self::$_callback));
        } else {
            self::$_properties[$class] = false;
        }
    }

    /**
     * Gets whether a class has a virtual property with a certain name.
     *
     * @param string $class
     * @param string $propName
     * @return boolean
     */
    public static function hasProperty($class, $propName)
    {
        return isset(self::$_properties[$class][$propName]);
    }

    /**
     * Gets the accessor for a virtual property.
     *
     * @param string $class
     * @param string $propName
     * @return string|null
     */
    public static function getAccessor($class, $propName)
    {
        return isset(self::$_properties[$class][$propName]['accessor']) ?
                self::$_properties[$class][$propName]['accessor'] : null;
    }

    /**
     * Sets the accessor method for a virtual property.
     *
     * @param <type> $class
     * @param <type> $propName
     * @param <type> $accessor
     */
    public static function setAccessor($class, $propName, $accessor)
    {
        self::$_properties[$class][$propName]['accessor'] = $accessor;
    }

    /**
     * Gets the mutator method for a virtual property.
     *
     * @param <type> $class
     * @param <type> $propName
     * @return <type>
     */
    public static function getMutator($class, $propName)
    {
        return isset(self::$_properties[$class][$propName]['mutator']) ?
                self::$_properties[$class][$propName]['mutator'] : null;
    }

    /**
     * Sets the mutator method for a virtual property.
     *
     * @param <type> $class
     * @param <type> $propName
     * @param <type> $mutator
     */
    public static function setMutator($class, $propName, $mutator)
    {
        self::$_properties[$class][$propName]['mutator'] = $mutator;
    }

    /**
     * Gets the type of a virtual property.
     *
     * @param <type> $class
     * @param <type> $propName
     * @return <type>
     */
    public static function getType($class, $propName)
    {
        return isset(self::$_properties[$class][$propName]['type']) ?
                self::$_properties[$class][$propName]['type'] : null;
    }

    /**
     * Sets the type of a virtual property.
     *
     * @param <type> $class
     * @param <type> $propName
     * @param <type> $type
     */
    public static function setType($class, $propName, $type)
    {
        self::$_properties[$class][$propName]['type'] = $type;
    }
}
?>
