<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id: expectation.php,v 1.35 2005/01/23 22:20:43 lastcraft Exp $
     */
     
    /**#@+
     *	include other SimpleTest class files
     */
    require_once(dirname(__FILE__) . '/dumper.php');
    require_once(dirname(__FILE__) . '/options.php');
    /**#@-*/
    
    /**
     *    Assertion that can display failure information.
     *    Also includes various helper methods.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     *    @abstract
     */
    class SimpleExpectation {
        var $_dumper;
        var $_message;
        
        /**
         *    Creates a dumper for displaying values and sets
         *    the test message.
         *    @param string $message    Customised message on failure.
         */
        function SimpleExpectation($message = '%s') {
            $this->_dumper = &new SimpleDumper();
            $this->_message = $message;
        }
        
        /**
         *    Tests the expectation. True if correct.
         *    @param mixed $compare        Comparison value.
         *    @return boolean              True if correct.
         *    @access public
         *    @abstract
         */
        function test($compare) {
        }
        
        /**
         *    Returns a human readable test message.
         *    @param mixed $compare      Comparison value.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         *    @abstract
         */
        function testMessage($compare) {
        }
        
        /**
         *    Overlays the generated message onto the stored user
         *    message.
         *    @param mixed $compare      Comparison value.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function overlayMessage($compare) {
            return sprintf($this->_message, $this->testMessage($compare));
        }
        
        /**
         *    Accessor for the dumper.
         *    @return SimpleDumper    Current value dumper.
         *    @access protected
         */
        function &_getDumper() {
            return $this->_dumper;
        }
    }
    
    /**
     *    Test for equality.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class EqualExpectation extends SimpleExpectation {
        var $_value;
        
        /**
         *    Sets the value to compare against.
         *    @param mixed $value        Test value to match.
         *    @param string $message     Customised message on failure.
         *    @access public
         */
        function EqualExpectation($value, $message = '%s') {
            $this->SimpleExpectation($message);
            $this->_value = $value;
        }
        
        /**
         *    Tests the expectation. True if it matches the
         *    held value.
         *    @param mixed $compare        Comparison value.
         *    @return boolean              True if correct.
         *    @access public
         */
        function test($compare, $nasty = false) {
            return (($this->_value == $compare) && ($compare == $this->_value));
        }
        
        /**
         *    Returns a human readable test message.
         *    @param mixed $compare      Comparison value.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function testMessage($compare) {
            if ($this->test($compare)) {
                return "Equal expectation [" . $this->_dumper->describeValue($this->_value) . "]";
            } else {
                return "Equal expectation fails " .
                        $this->_dumper->describeDifference($this->_value, $compare);
            }
        }

        /**
         *    Accessor for comparison value.
         *    @return mixed       Held value to compare with.
         *    @access protected
         */
        function _getValue() {
            return $this->_value;
        }
    }
    
    /**
     *    Test for inequality.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class NotEqualExpectation extends EqualExpectation {
        
        /**
         *    Sets the value to compare against.
         *    @param mixed $value       Test value to match.
         *    @param string $message    Customised message on failure.
         *    @access public
         */
        function NotEqualExpectation($value, $message = '%s') {
            $this->EqualExpectation($value, $message);
        }
        
        /**
         *    Tests the expectation. True if it differs from the
         *    held value.
         *    @param mixed $compare        Comparison value.
         *    @return boolean              True if correct.
         *    @access public
         */
        function test($compare) {
            return ! parent::test($compare);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param mixed $compare      Comparison value.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function testMessage($compare) {
            $dumper = &$this->_getDumper();
            if ($this->test($compare)) {
                return "Not equal expectation passes " .
                        $dumper->describeDifference($this->_getValue(), $compare);
            } else {
                return "Not equal expectation fails [" .
                        $dumper->describeValue($this->_getValue()) .
                        "] matches";
            }
        }
    }
    
    /**
     *    Test for identity.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class IdenticalExpectation extends EqualExpectation {
        
        /**
         *    Sets the value to compare against.
         *    @param mixed $value       Test value to match.
         *    @param string $message    Customised message on failure.
         *    @access public
         */
        function IdenticalExpectation($value, $message = '%s') {
            $this->EqualExpectation($value, $message);
        }
        
        /**
         *    Tests the expectation. True if it exactly
         *    matches the held value.
         *    @param mixed $compare        Comparison value.
         *    @return boolean              True if correct.
         *    @access public
         */
        function test($compare) {
            return SimpleTestCompatibility::isIdentical($this->_getValue(), $compare);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param mixed $compare      Comparison value.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function testMessage($compare) {
            $dumper = &$this->_getDumper();
            if ($this->test($compare)) {
                return "Identical expectation [" . $dumper->describeValue($this->_getValue()) . "]";
            } else {
                return "Identical expectation [" . $dumper->describeValue($this->_getValue()) .
                        "] fails with [" .
                        $dumper->describeValue($compare) . "] " .
                        $dumper->describeDifference($this->_getValue(), $compare, TYPE_MATTERS);
            }
        }
    }
    
    /**
     *    Test for non-identity.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class NotIdenticalExpectation extends IdenticalExpectation {
        
        /**
         *    Sets the value to compare against.
         *    @param mixed $value        Test value to match.
         *    @param string $message     Customised message on failure.
         *    @access public
         */
        function NotIdenticalExpectation($value, $message = '%s') {
            $this->IdenticalExpectation($value, $message);
        }
        
        /**
         *    Tests the expectation. True if it differs from the
         *    held value.
         *    @param mixed $compare        Comparison value.
         *    @return boolean              True if correct.
         *    @access public
         */
        function test($compare) {
            return ! parent::test($compare);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param mixed $compare      Comparison value.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function testMessage($compare) {
            $dumper = &$this->_getDumper();
            if ($this->test($compare)) {
                return "Not identical expectation passes " .
                        $dumper->describeDifference($this->_getValue(), $compare, TYPE_MATTERS);
            } else {
                return "Not identical expectation [" . $dumper->describeValue($this->_getValue()) . "] matches";
            }
        }
    }
    
    /**
     *    Test for a pattern using Perl regex rules.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class WantedPatternExpectation extends SimpleExpectation {
        var $_pattern;
        
        /**
         *    Sets the value to compare against.
         *    @param string $pattern    Pattern to search for.
         *    @param string $message    Customised message on failure.
         *    @access public
         */
        function WantedPatternExpectation($pattern, $message = '%s') {
            $this->SimpleExpectation($message);
            $this->_pattern = $pattern;
        }
        
        /**
         *    Accessor for the pattern.
         *    @return string       Perl regex as string.
         *    @access protected
         */
        function _getPattern() {
            return $this->_pattern;
        }
        
        /**
         *    Tests the expectation. True if the Perl regex
         *    matches the comparison value.
         *    @param string $compare        Comparison value.
         *    @return boolean               True if correct.
         *    @access public
         */
        function test($compare) {
            return (boolean)preg_match($this->_getPattern(), $compare);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param mixed $compare      Comparison value.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function testMessage($compare) {
            if ($this->test($compare)) {
                return $this->_describePatternMatch($this->_getPattern(), $compare);
            } else {
                $dumper = &$this->_getDumper();
                return "Pattern [" . $this->_getPattern() .
                        "] not detected in [" .
                        $dumper->describeValue($compare) . "]";
            }
        }
        
        /**
         *    Describes a pattern match including the string
         *    found and it's position.
         *    @param string $pattern        Regex to match against.
         *    @param string $subject        Subject to search.
         *    @access protected
         */
        function _describePatternMatch($pattern, $subject) {
            preg_match($pattern, $subject, $matches);
            $position = strpos($subject, $matches[0]);
            $dumper = &$this->_getDumper();
            return "Pattern [$pattern] detected at character [$position] in [" .
                    $dumper->describeValue($subject) . "] as [" .
                    $matches[0] . "] in region [" .
                    $dumper->clipString($subject, 100, $position) . "]";
        }
    }
    
    /**
     *    Fail if a pattern is detected within the
     *    comparison.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class UnwantedPatternExpectation extends WantedPatternExpectation {
        
        /**
         *    Sets the reject pattern
         *    @param string $pattern    Pattern to search for.
         *    @param string $message    Customised message on failure.
         *    @access public
         */
        function UnwantedPatternExpectation($pattern, $message = '%s') {
            $this->WantedPatternExpectation($pattern, $message);
        }
        
        /**
         *    Tests the expectation. False if the Perl regex
         *    matches the comparison value.
         *    @param string $compare        Comparison value.
         *    @return boolean               True if correct.
         *    @access public
         */
        function test($compare) {
            return ! parent::test($compare);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param string $compare      Comparison value.
         *    @return string              Description of success
         *                                or failure.
         *    @access public
         */
        function testMessage($compare) {
            if ($this->test($compare)) {
                $dumper = &$this->_getDumper();
                return "Pattern [" . $this->_getPattern() .
                        "] not detected in [" .
                        $dumper->describeValue($compare) . "]";
            } else {
                return $this->_describePatternMatch($this->_getPattern(), $compare);
            }
        }
    }
    
    /**
     *    Tests either type or class name if it's an object.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class IsAExpectation extends SimpleExpectation {
        var $_type;
        
        /**
         *    Sets the type to compare with.
         *    @param string $type       Type or class name.
         *    @param string $message    Customised message on failure.
         *    @access public
         */
        function IsAExpectation($type, $message = '%s') {
            $this->SimpleExpectation($message);
            $this->_type = $type;
        }
        
        /**
         *    Accessor for type to check against.
         *    @return string    Type or class name.
         *    @access protected
         */
        function _getType() {
            return $this->_type;
        }
        
        /**
         *    Tests the expectation. True if the type or
         *    class matches the string value.
         *    @param string $compare        Comparison value.
         *    @return boolean               True if correct.
         *    @access public
         */
        function test($compare) {
            if (is_object($compare)) {
                return SimpleTestCompatibility::isA($compare, $this->_type);
            } else {
                return (strtolower(gettype($compare)) == $this->_canonicalType($this->_type));
            }
        }

        /**
         *    Coerces type name into a gettype() match.
         *    @param string $type        User type.
         *    @return string             Simpler type.
         *    @access private
         */
        function _canonicalType($type) {
            $type = strtolower($type);
            $map = array(
                    'bool' => 'boolean',
                    'float' => 'double',
                    'real' => 'double',
                    'int' => 'integer');
            if (isset($map[$type])) {
                $type = $map[$type];
            }
            return $type;
        }

        /**
         *    Returns a human readable test message.
         *    @param mixed $compare      Comparison value.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function testMessage($compare) {
            $dumper = &$this->_getDumper();
            return "Value [" . $dumper->describeValue($compare) .
                    "] should be type [" . $this->_type . "]";
        }
    }
    
    /**
     *    Tests either type or class name if it's an object.
     *    Will succeed if the type does not match.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class NotAExpectation extends IsAExpectation {
        var $_type;
        
        /**
         *    Sets the type to compare with.
         *    @param string $type       Type or class name.
         *    @param string $message    Customised message on failure.
         *    @access public
         */
        function NotAExpectation($type, $message = '%s') {
            $this->IsAExpectation($type, $message);
        }
        
        /**
         *    Tests the expectation. False if the type or
         *    class matches the string value.
         *    @param string $compare        Comparison value.
         *    @return boolean               True if different.
         *    @access public
         */
        function test($compare) {
            return ! parent::test($compare);
        }

        /**
         *    Returns a human readable test message.
         *    @param mixed $compare      Comparison value.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function testMessage($compare) {
            $dumper = &$this->_getDumper();
            return "Value [" . $dumper->describeValue($compare) .
                    "] should not be type [" . $this->_getType() . "]";
        }
    }

    /**
     *    Tests for existance of a method in an object
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class MethodExistsExpectation extends SimpleExpectation {
        var $_method;
        
        /**
         *    Sets the value to compare against.
         *    @param string $method     Method to check.
         *    @param string $message    Customised message on failure.
         *    @access public
         *    @return void
         */
        function MethodExistsExpectation($method, $message = '%s') {
            $this->SimpleExpectation($message);
            $this->_method = &$method;
        }
        
        /**
         *    Tests the expectation. True if the method exists in the test object.
         *    @param string $compare        Comparison method name.
         *    @return boolean               True if correct.
         *    @access public
         */
        function test($compare) {
            return (boolean)(is_object($compare) && method_exists($compare, $this->_method));
        }
        
        /**
         *    Returns a human readable test message.
         *    @param mixed $compare      Comparison value.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function testMessage($compare) {
			$dumper = &$this->_getDumper();
			if (! is_object($compare)) {
			    return 'No method on non-object [' . $dumper->describeValue($compare) . ']';
			}
			$method = $this->_method;
			return "Object [" . $dumper->describeValue($compare) .
					"] should contain method [$method]";
        }
    }
?>