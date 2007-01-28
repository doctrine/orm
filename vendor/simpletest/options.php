<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@version	$Id: options.php,v 1.35 2005/02/04 03:48:52 lastcraft Exp $
     */
    
    /**
     *    Static global directives and options.
     *	  @package	SimpleTest
     */
    class SimpleTestOptions {
        
        /**
         *    Reads the SimpleTest version from the release file.
         *    @return string        Version string.
         *    @static
         *    @access public
         */
        function getVersion() {
            $content = file(dirname(__FILE__) . '/VERSION');
            return trim($content[0]);
        }
        
        /**
         *    Sets the name of a test case to ignore, usually
         *    because the class is an abstract case that should
         *    not be run.
         *    @param string $class        Add a class to ignore.
         *    @static
         *    @access public
         */
        function ignore($class) {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['IgnoreList'][] = strtolower($class);
        }
        
        /**
         *    Test to see if a test case is in the ignore
         *    list.
         *    @param string $class        Class name to test.
         *    @return boolean             True if should not be run.
         *    @access public
         *    @static
         */
        function isIgnored($class) {
            $registry = &SimpleTestOptions::_getRegistry();
            return in_array(strtolower($class), $registry['IgnoreList']);
        }
        
        /**
         *    The base class name is settable here. This is the
         *    class that a new stub will inherited from.
         *    To modify the generated stubs simply extend the
         *    SimpleStub class and set it's name
         *    with this method before any stubs are generated.
         *    @param string $stub_base     Server stub class to use.
         *    @static
         *    @access public
         */
        function setStubBaseClass($stub_base) {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['StubBaseClass'] = $stub_base;
        }
        
        /**
         *    Accessor for the currently set stub base class.
         *    @return string        Class name to inherit from.
         *    @static
         *    @access public
         */
        function getStubBaseClass() {
            $registry = &SimpleTestOptions::_getRegistry();
            return $registry['StubBaseClass'];
        }
        
        /**
         *    The base class name is settable here. This is the
         *    class that a new mock will inherited from.
         *    To modify the generated mocks simply extend the
         *    SimpleMock class and set it's name
         *    with this method before any mocks are generated.
         *    @param string $mock_base        Mock base class to use.
         *    @static
         *    @access public
         */
        function setMockBaseClass($mock_base) {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['MockBaseClass'] = $mock_base;
        }
        
        /**
         *    Accessor for the currently set mock base class.
         *    @return string           Class name to inherit from.
         *    @static
         *    @access public
         */
        function getMockBaseClass() {
            $registry = &SimpleTestOptions::_getRegistry();
            return $registry['MockBaseClass'];
        }
        
        /**
         *    Adds additional mock code.
         *    @param string $code    Extra code that can be added
         *                           to the partial mocks for
         *                           extra functionality. Useful
         *                           when a test tool has overridden
         *                           the mock base classes.
         *    @access public
         */
        function addPartialMockCode($code = '') {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['AdditionalPartialMockCode'] = $code;
        }
        
        /**
         *    Accessor for additional partial mock code.
         *    @return string       Extra code.
         *    @access public
         */
        function getPartialMockCode() {
            $registry = &SimpleTestOptions::_getRegistry();
            return $registry['AdditionalPartialMockCode'];
        }
        
        /**
         *    Sets proxy to use on all requests for when
         *    testing from behind a firewall. Set host
         *    to false to disable. This will take effect
         *    if there are no other proxy settings.
         *    @param string $proxy     Proxy host as URL.
         *    @param string $username  Proxy username for authentication.
         *    @param string $password  Proxy password for authentication.
         *    @access public
         */
        function useProxy($proxy, $username = false, $password = false) {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['DefaultProxy'] = $proxy;
            $registry['DefaultProxyUsername'] = $username;
            $registry['DefaultProxyPassword'] = $password;
        }
        
        /**
         *    Accessor for default proxy host.
         *    @return string       Proxy URL.
         *    @access public
         */
        function getDefaultProxy() {
            $registry = &SimpleTestOptions::_getRegistry();
            return $registry['DefaultProxy'];
        }
        
        /**
         *    Accessor for default proxy username.
         *    @return string    Proxy username for authentication.
         *    @access public
         */
        function getDefaultProxyUsername() {
            $registry = &SimpleTestOptions::_getRegistry();
            return $registry['DefaultProxyUsername'];
        }
        
        /**
         *    Accessor for default proxy password.
         *    @return string    Proxy password for authentication.
         *    @access public
         */
        function getDefaultProxyPassword() {
            $registry = &SimpleTestOptions::_getRegistry();
            return $registry['DefaultProxyPassword'];
        }
        
        /**
         *    Accessor for global registry of options.
         *    @return hash           All stored values.
         *    @access private
         *    @static
         */
        function &_getRegistry() {
            static $registry = false;
            if (! $registry) {
                $registry = SimpleTestOptions::_getDefaults();
            }
            return $registry;
        }
        
        /**
         *    Constant default values.
         *    @return hash       All registry defaults.
         *    @access private
         *    @static
         */
        function _getDefaults() {
            return array(
                    'StubBaseClass' => 'SimpleStub',
                    'MockBaseClass' => 'SimpleMock',
                    'IgnoreList' => array(),
                    'AdditionalPartialMockCode' => '',
                    'DefaultProxy' => false,
                    'DefaultProxyUsername' => false,
                    'DefaultProxyPassword' => false);
        }
    }
    
    /**
     *  Static methods for compatibility between different
     *  PHP versions.
     *  @package	SimpleTest
     */
    class SimpleTestCompatibility {
        
        /**
         *    Identity test. Drops back to equality + types for PHP5
         *    objects as the === operator counts as the
         *    stronger reference constraint.
         *    @param mixed $first    Test subject.
         *    @param mixed $second   Comparison object.
         *    @access public
         *    @static
         */
        function isIdentical($first, $second) {
            if ($first != $second) {
                return false;
            }
            if (version_compare(phpversion(), '5') >= 0) {
                return SimpleTestCompatibility::_isIdenticalType($first, $second);
            }
            return ($first === $second);
        }
        
        /**
         *    Recursive type test.
         *    @param mixed $first    Test subject.
         *    @param mixed $second   Comparison object.
         *    @access private
         *    @static
         */
        function _isIdenticalType($first, $second) {
            if (gettype($first) != gettype($second)) {
                return false;
            }
            if (is_object($first) && is_object($second)) {
                if (get_class($first) != get_class($second)) {
                    return false;
                }
                return SimpleTestCompatibility::_isArrayOfIdenticalTypes(
                        get_object_vars($first),
                        get_object_vars($second));
            }
            if (is_array($first) && is_array($second)) {
                return SimpleTestCompatibility::_isArrayOfIdenticalTypes($first, $second);
            }
            return true;
        }
        
        /**
         *    Recursive type test for each element of an array.
         *    @param mixed $first    Test subject.
         *    @param mixed $second   Comparison object.
         *    @access private
         *    @static
         */
        function _isArrayOfIdenticalTypes($first, $second) {
            if (array_keys($first) != array_keys($second)) {
                return false;
            }
            foreach (array_keys($first) as $key) {
                $is_identical = SimpleTestCompatibility::_isIdenticalType(
                        $first[$key],
                        $second[$key]);
                if (! $is_identical) {
                    return false;
                }
            }
            return true;
        }
        
        /**
         *    Test for two variables being aliases.
         *    @param mixed $first    Test subject.
         *    @param mixed $second   Comparison object.
         *    @access public
         *    @static
         */
        function isReference(&$first, &$second) {
            if (version_compare(phpversion(), '5', '>=')
	    	    && is_object($first)) {
	    	    return ($first === $second);
	        }
	        $temp = $first;
            $first = uniqid("test");
            $is_ref = ($first === $second);
            $first = $temp;
            return $is_ref;
        }
        
        /**
         *    Test to see if an object is a member of a
         *    class hiearchy.
         *    @param object $object    Object to test.
         *    @param string $class     Root name of hiearchy.
         *    @access public
         *    @static
         */
        static function isA($object, $class) {
            if (version_compare(phpversion(), '5') >= 0) {
                if (! class_exists($class, false)) {
                    return false;
                }
                eval("\$is_a = \$object instanceof $class;");
                return $is_a;
            }
            if (function_exists('is_a')) {
                return is_a($object, $class);
            }
            return ((strtolower($class) == get_class($object))
                    or (is_subclass_of($object, $class)));
        }
        
        /**
         *    Autoload safe version of class_exists().
         *    @param string $class        Name of class to look for.
         *    @return boolean             True if class is defined.
         *    @access public
         *    @static
         */
        function classExists($class) {
            if (version_compare(phpversion(), '5') >= 0) {
                return class_exists($class, false);
            } else {
                return class_exists($class);
            }
        }
        
        /**
         *    Sets a socket timeout for each chunk.
         *    @param resource $handle    Socket handle.
         *    @param integer $timeout    Limit in seconds.
         *    @access public
         *    @static
         */
        function setTimeout($handle, $timeout) {
            if (function_exists('stream_set_timeout')) {
                stream_set_timeout($handle, $timeout, 0);
            } elseif (function_exists('socket_set_timeout')) {
                socket_set_timeout($handle, $timeout, 0);
            } elseif (function_exists('set_socket_timeout')) {
                set_socket_timeout($handle, $timeout, 0);
            }
        }
        
        /**
         *    Gets the current stack trace topmost first.
         *    @return array        List of stack frames.
         *    @access public
         *    @static
         */
        static function getStackTrace() {
            if (function_exists('debug_backtrace')) {
                return array_reverse(debug_backtrace());
            }
            return array();
        }
    }
?>
