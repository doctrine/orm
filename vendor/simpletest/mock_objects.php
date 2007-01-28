<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	MockObjects
     *	@version	$Id: mock_objects.php,v 1.61 2005/02/13 01:09:25 lastcraft Exp $
     */

    /**#@+
     * include SimpleTest files
     */
    require_once(dirname(__FILE__) . '/expectation.php');
    require_once(dirname(__FILE__) . '/options.php');
    require_once(dirname(__FILE__) . '/dumper.php');
    /**#@-*/
    
    /**
     * Default character simpletest will substitute for any value
     */
    define('MOCK_WILDCARD', '*');
    
    /**
     *    A wildcard expectation always matches.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class WildcardExpectation extends SimpleExpectation {
        
        /**
         *    Chains constructor only.
         *    @access public
         */
        function WildcardExpectation() {
            $this->SimpleExpectation();
        }
        
        /**
         *    Tests the expectation. Always true.
         *    @param mixed $compare  Ignored.
         *    @return boolean        True.
         *    @access public
         */
        function test($compare) {
            return true;
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
            return 'Wildcard always matches [' . $dumper->describeValue($compare) . ']';
        }
    }
    
    /**
     *    Parameter comparison assertion.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class ParametersExpectation extends SimpleExpectation {
        var $_expected;
        
        /**
         *    Sets the expected parameter list.
         *    @param array $parameters  Array of parameters including
         *                              those that are wildcarded.
         *                              If the value is not an array
         *                              then it is considered to match any.
         *    @param mixed $wildcard    Any parameter matching this
         *                              will always match.
         *    @param string $message    Customised message on failure.
         *    @access public
         */
        function ParametersExpectation($expected = false, $message = '%s') {
            $this->SimpleExpectation($message);
            $this->_expected = $expected;
        }
        
        /**
         *    Tests the assertion. True if correct.
         *    @param array $parameters     Comparison values.
         *    @return boolean              True if correct.
         *    @access public
         */
        function test($parameters) {
            if (! is_array($this->_expected)) {
                return true;
            }
            if (count($this->_expected) != count($parameters)) {
                return false;
            }
            for ($i = 0; $i < count($this->_expected); $i++) {
                if (! $this->_testParameter($parameters[$i], $this->_expected[$i])) {
                    return false;
                }
            }
            return true;
        }
        
        /**
         *    Tests an individual parameter.
         *    @param mixed $parameter    Value to test.
         *    @param mixed $expected     Comparison value.
         *    @return boolean            True if expectation
         *                               fulfilled.
         *    @access private
         */
        function _testParameter($parameter, $expected) {
            $comparison = $this->_coerceToExpectation($expected);
            return $comparison->test($parameter);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param array $comparison   Incoming parameter list.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function testMessage($parameters) {
            if ($this->test($parameters)) {
                return "Expectation of " . count($this->_expected) .
                        " arguments of [" . $this->_renderArguments($this->_expected) .
                        "] is correct";
            } else {
                return $this->_describeDifference($this->_expected, $parameters);
            }
        }
        
        /**
         *    Message to display if expectation differs from
         *    the parameters actually received.
         *    @param array $expected      Expected parameters as list.
         *    @param array $parameters    Actual parameters received.
         *    @return string              Description of difference.
         *    @access private
         */
        function _describeDifference($expected, $parameters) {
            if (count($expected) != count($parameters)) {
                return "Expected " . count($expected) .
                        " arguments of [" . $this->_renderArguments($expected) .
                        "] but got " . count($parameters) .
                        " arguments of [" . $this->_renderArguments($parameters) . "]";
            }
            $messages = array();
            for ($i = 0; $i < count($expected); $i++) {
                $comparison = $this->_coerceToExpectation($expected[$i]);
                if (! $comparison->test($parameters[$i])) {
                    $messages[] = "parameter " . ($i + 1) . " with [" .
                            $comparison->overlayMessage($parameters[$i]) . "]";
                }
            }
            return "Parameter expectation differs at " . implode(" and ", $messages);
        }
        
        /**
         *    Creates an identical expectation if the
         *    object/value is not already some type
         *    of expectation.
         *    @param mixed $expected      Expected value.
         *    @return SimpleExpectation   Expectation object.
         *    @access private
         */
        function _coerceToExpectation($expected) {
            if (SimpleTestCompatibility::isA($expected, 'SimpleExpectation')) {
                return $expected;
            }
            return new IdenticalExpectation($expected);
        }
        
        /**
         *    Renders the argument list as a string for
         *    messages.
         *    @param array $args    Incoming arguments.
         *    @return string        Simple description of type and value.
         *    @access private
         */
        function _renderArguments($args) {
            $descriptions = array();
            if (is_array($args)) {
                foreach ($args as $arg) {
                    $dumper = &new SimpleDumper();
                    $descriptions[] = $dumper->describeValue($arg);
                }
            }
            return implode(', ', $descriptions);
        }
    }
    
    /**
     *    Confirms that the number of calls on a method is as expected.
     */
    class CallCountExpectation extends SimpleExpectation {
        var $_method;
        var $_count;
        
        /**
         *    Stashes the method and expected count for later
         *    reporting.
         *    @param string $method    Name of method to confirm against.
         *    @param integer $count    Expected number of calls.
         *    @param string $message   Custom error message.
         */
        function CallCountExpectation($method, $count, $message = '%s') {
            $this->_method = $method;
            $this->_count = $count;
            $this->SimpleExpectation($message);
        }
        
        /**
         *    Tests the assertion. True if correct.
         *    @param integer $compare     Measured call count.
         *    @return boolean             True if expected.
         *    @access public
         */
        function test($compare) {
            return ($this->_count == $compare);
        }
        
        /**
         *    Reports the comparison.
         *    @param integer $compare     Measured call count.
         *    @return string              Message to show.
         *    @access public
         */
        function testMessage($compare) {
            return 'Expected call count for [' . $this->_method .
                    '] was [' . $this->_count .
                    '] got [' . $compare . ']';
        }
    }
      
    /**
     *    Confirms that the number of calls on a method is as expected.
     */
    class MinimumCallCountExpectation extends SimpleExpectation {
        var $_method;
        var $_count;
        
        /**
         *    Stashes the method and expected count for later
         *    reporting.
         *    @param string $method    Name of method to confirm against.
         *    @param integer $count    Minimum number of calls.
         *    @param string $message   Custom error message.
         */
        function MinimumCallCountExpectation($method, $count, $message = '%s') {
            $this->_method = $method;
            $this->_count = $count;
            $this->SimpleExpectation($message);
        }
        
        /**
         *    Tests the assertion. True if correct.
         *    @param integer $compare     Measured call count.
         *    @return boolean             True if enough.
         *    @access public
         */
        function test($compare) {
            return ($this->_count <= $compare);
        }
        
        /**
         *    Reports the comparison.
         *    @param integer $compare     Measured call count.
         *    @return string              Message to show.
         *    @access public
         */
        function testMessage($compare) {
            return 'Minimum call count for [' . $this->_method .
                    '] was [' . $this->_count .
                    '] got [' . $compare . ']';
        }
    }
        
    /**
     *    Confirms that the number of calls on a method is as expected.
     */
    class MaximumCallCountExpectation extends SimpleExpectation {
        var $_method;
        var $_count;
        
        /**
         *    Stashes the method and expected count for later
         *    reporting.
         *    @param string $method    Name of method to confirm against.
         *    @param integer $count    Minimum number of calls.
         *    @param string $message   Custom error message.
         */
        function MaximumCallCountExpectation($method, $count, $message = '%s') {
            $this->_method = $method;
            $this->_count = $count;
            $this->SimpleExpectation($message);
        }
        
        /**
         *    Tests the assertion. True if correct.
         *    @param integer $compare     Measured call count.
         *    @return boolean             True if not over.
         *    @access public
         */
        function test($compare) {
            return ($this->_count >= $compare);
        }
        
        /**
         *    Reports the comparison.
         *    @param integer $compare     Measured call count.
         *    @return string              Message to show.
         *    @access public
         */
        function testMessage($compare) {
            return 'Maximum call count for [' . $this->_method .
                    '] was [' . $this->_count .
                    '] got [' . $compare . ']';
        }
    }

    /**
     *    Retrieves values and references by searching the
     *    parameter lists until a match is found.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class CallMap {
        var $_map;
        
        /**
         *    Creates an empty call map.
         *    @access public
         */
        function CallMap() {
            $this->_map = array();
        }
        
        /**
         *    Stashes a value against a method call.
         *    @param array $parameters    Arguments including wildcards.
         *    @param mixed $value         Value copied into the map.
         *    @access public
         */
        function addValue($parameters, $value) {
            $this->addReference($parameters, $value);
        }
        
        /**
         *    Stashes a reference against a method call.
         *    @param array $parameters    Array of arguments (including wildcards).
         *    @param mixed $reference     Array reference placed in the map.
         *    @access public
         */
        function addReference($parameters, &$reference) {
            $place = count($this->_map);
            $this->_map[$place] = array();
            $this->_map[$place]["params"] = new ParametersExpectation($parameters);
            $this->_map[$place]["content"] = &$reference;
        }
        
        /**
         *    Searches the call list for a matching parameter
         *    set. Returned by reference.
         *    @param array $parameters    Parameters to search by
         *                                without wildcards.
         *    @return object              Object held in the first matching
         *                                slot, otherwise null.
         *    @access public
         */
        function &findFirstMatch($parameters) {
            $slot = $this->_findFirstSlot($parameters);
            if (!isset($slot)) {
                return null;
            }
            return $slot["content"];
        }
        
        /**
         *    Searches the call list for a matching parameter
         *    set. True if successful.
         *    @param array $parameters    Parameters to search by
         *                                without wildcards.
         *    @return boolean             True if a match is present.
         *    @access public
         */
        function isMatch($parameters) {
            return ($this->_findFirstSlot($parameters) != null);
        }
        
        /**
         *    Searches the map for a matching item.
         *    @param array $parameters    Parameters to search by
         *                                without wildcards.
         *    @return array               Reference to slot or null.
         *    @access private
         */
        function &_findFirstSlot($parameters) {
            $count = count($this->_map);
            for ($i = 0; $i < $count; $i++) {
                if ($this->_map[$i]["params"]->test($parameters)) {
                    return $this->_map[$i];
                }
            }
            return null;
        }
    }
    
    /**
     *    An empty collection of methods that can have their
     *    return values set. Used for prototyping.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class SimpleStub {
        var $_wildcard;
        var $_is_strict;
        var $_returns;
        var $_return_sequence;
        var $_call_counts;
        
        /**
         *    Sets up the wildcard and everything else empty.
         *    @param mixed $wildcard      Parameter matching wildcard.
         *    @param boolean $is_strict   Enables method name checks.
         *    @access public
         */
        function SimpleStub($wildcard, $is_strict = true) {
            $this->_wildcard = $wildcard;
            $this->_is_strict = $is_strict;
            $this->_returns = array();
            $this->_return_sequence = array();
            $this->_call_counts = array();
        }
        
        /**
         *    Replaces wildcard matches with wildcard
         *    expectations in the argument list.
         *    @param array $args      Raw argument list.
         *    @return array           Argument list with
         *                            expectations.
         *    @access private
         */
        function _replaceWildcards($args) {
            if ($args === false) {
                return false;
            }
            for ($i = 0; $i < count($args); $i++) {
                if ($args[$i] === $this->_wildcard) {
                    $args[$i] = new WildcardExpectation();
                }
            }
            return $args;
        }
        
        /**
         *    Returns the expected value for the method name.
         *    @param string $method       Name of method to simulate.
         *    @param array $args          Arguments as an array.
         *    @return mixed               Stored return.
         *    @access private
         */
        function &_invoke($method, $args) {
            $method = strtolower($method);
            $step = $this->getCallCount($method);
            $this->_addCall($method, $args);
            return $this->_getReturn($method, $args, $step);
        }
        
        /**
         *    Triggers a PHP error if the method is not part
         *    of this object.
         *    @param string $method        Name of method.
         *    @param string $task          Description of task attempt.
         *    @access protected
         */
        function _dieOnNoMethod($method, $task) {
            if ($this->_is_strict && !method_exists($this, $method)) {
                trigger_error(
                        "Cannot $task as no ${method}() in class " . get_class($this),
                        E_USER_ERROR);
            }
        }
        
        /**
         *    Adds one to the call count of a method.
         *    @param string $method        Method called.
         *    @param array $args           Arguments as an array.
         *    @access protected
         */
        function _addCall($method, $args) {
            if (!isset($this->_call_counts[$method])) {
                $this->_call_counts[$method] = 0;
            }
            $this->_call_counts[$method]++;
        }
        
        /**
         *    Fetches the call count of a method so far.
         *    @param string $method        Method name called.
         *    @return                      Number of calls so far.
         *    @access public
         */
        function getCallCount($method) {
            $this->_dieOnNoMethod($method, "get call count");
            $method = strtolower($method);
            if (! isset($this->_call_counts[$method])) {
                return 0;
            }
            return $this->_call_counts[$method];
        }
        
        /**
         *    Sets a return for a parameter list that will
         *    be passed by value for all calls to this method.
         *    @param string $method       Method name.
         *    @param mixed $value         Result of call passed by value.
         *    @param array $args          List of parameters to match
         *                                including wildcards.
         *    @access public
         */
        function setReturnValue($method, $value, $args = false) {
            $this->_dieOnNoMethod($method, "set return value");
            $args = $this->_replaceWildcards($args);
            $method = strtolower($method);
            if (! isset($this->_returns[$method])) {
                $this->_returns[$method] = new CallMap();
            }
            $this->_returns[$method]->addValue($args, $value);
        }
                
        /**
         *    Sets a return for a parameter list that will
         *    be passed by value only when the required call count
         *    is reached.
         *    @param integer $timing   Number of calls in the future
         *                             to which the result applies. If
         *                             not set then all calls will return
         *                             the value.
         *    @param string $method    Method name.
         *    @param mixed $value      Result of call passed by value.
         *    @param array $args       List of parameters to match
         *                             including wildcards.
         *    @access public
         */
        function setReturnValueAt($timing, $method, $value, $args = false) {
            $this->_dieOnNoMethod($method, "set return value sequence");
            $args = $this->_replaceWildcards($args);
            $method = strtolower($method);
            if (! isset($this->_return_sequence[$method])) {
                $this->_return_sequence[$method] = array();
            }
            if (! isset($this->_return_sequence[$method][$timing])) {
                $this->_return_sequence[$method][$timing] = new CallMap();
            }
            $this->_return_sequence[$method][$timing]->addValue($args, $value);
        }
         
        /**
         *    Sets a return for a parameter list that will
         *    be passed by reference for all calls.
         *    @param string $method       Method name.
         *    @param mixed $reference     Result of the call will be this object.
         *    @param array $args          List of parameters to match
         *                                including wildcards.
         *    @access public
         */
        function setReturnReference($method, &$reference, $args = false) {
            $this->_dieOnNoMethod($method, "set return reference");
            $args = $this->_replaceWildcards($args);
            $method = strtolower($method);
            if (! isset($this->_returns[$method])) {
                $this->_returns[$method] = new CallMap();
            }
            $this->_returns[$method]->addReference($args, $reference);
        }
        
        /**
         *    Sets a return for a parameter list that will
         *    be passed by value only when the required call count
         *    is reached.
         *    @param integer $timing    Number of calls in the future
         *                              to which the result applies. If
         *                              not set then all calls will return
         *                              the value.
         *    @param string $method     Method name.
         *    @param mixed $reference   Result of the call will be this object.
         *    @param array $args        List of parameters to match
         *                              including wildcards.
         *    @access public
         */
        function setReturnReferenceAt($timing, $method, &$reference, $args = false) {
            $this->_dieOnNoMethod($method, "set return reference sequence");
            $args = $this->_replaceWildcards($args);
            $method = strtolower($method);
            if (! isset($this->_return_sequence[$method])) {
                $this->_return_sequence[$method] = array();
            }
            if (! isset($this->_return_sequence[$method][$timing])) {
                $this->_return_sequence[$method][$timing] = new CallMap();
            }
            $this->_return_sequence[$method][$timing]->addReference($args, $reference);
        }
        
        /**
         *    Finds the return value matching the incoming
         *    arguments. If there is no matching value found
         *    then an error is triggered.
         *    @param string $method      Method name.
         *    @param array $args         Calling arguments.
         *    @param integer $step       Current position in the
         *                               call history.
         *    @return mixed              Stored return.
         *    @access protected
         */
        function &_getReturn($method, $args, $step) {
            if (isset($this->_return_sequence[$method][$step])) {
                if ($this->_return_sequence[$method][$step]->isMatch($args)) {
                    return $this->_return_sequence[$method][$step]->findFirstMatch($args);
                }
            }
            if (isset($this->_returns[$method])) {
                return $this->_returns[$method]->findFirstMatch($args);
            }
            return null;
        }
    }
    
    /**
     *    An empty collection of methods that can have their
     *    return values set and expectations made of the
     *    calls upon them. The mock will assert the
     *    expectations against it's attached test case in
     *    addition to the server stub behaviour.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class SimpleMock extends SimpleStub {
        var $_test;
        var $_expected_counts;
        var $_max_counts;
        var $_expected_args;
        var $_expected_args_at;
        
        /**
         *    Creates an empty return list and expectation list.
         *    All call counts are set to zero.
         *    @param SimpleTestCase $test    Test case to test expectations in.
         *    @param mixed $wildcard         Parameter matching wildcard.
         *    @param boolean $is_strict      Enables method name checks on
         *                                   expectations.
         *    @access public
         */
        function SimpleMock(&$test, $wildcard, $is_strict = true) {
            $this->SimpleStub($wildcard, $is_strict);
            if (! $test) {
                trigger_error('No unit tester for mock object', E_USER_ERROR);
                return;
            }
            $this->_test = SimpleMock::registerTest($test);
            $this->_expected_counts = array();
            $this->_max_counts = array();
            $this->_expected_args = array();
            $this->_expected_args_at = array();
        }
        
        /**
         *    Accessor for attached unit test so that when
         *    subclassed, new expectations can be added easily.
         *    @return SimpleTestCase      Unit test passed in constructor.
         *    @access public
         */
        function &getTest() {
            return $this->_test;
        }
        
        /**
         *    Die if bad arguments array is passed
         *    @param mixed $args     The arguments value to be checked.
         *    @param string $task    Description of task attempt.
         *    @return boolean        Valid arguments
         *    @access private
         */
        function _checkArgumentsIsArray($args, $task) {
        	if (! is_array($args)) {
        		trigger_error(
        			"Cannot $task as \$args parameter is not an array",
        			E_USER_ERROR);
        	}
        }
        
        /**
         *    Sets up an expected call with a set of
         *    expected parameters in that call. All
         *    calls will be compared to these expectations
         *    regardless of when the call is made.
         *    @param string $method        Method call to test.
         *    @param array $args           Expected parameters for the call
         *                                 including wildcards.
         *    @param string $message       Overridden message.
         *    @access public
         */
        function expectArguments($method, $args, $message = '%s') {
            $this->_dieOnNoMethod($method, 'set expected arguments');
            $this->_checkArgumentsIsArray($args, 'set expected arguments');
            $args = $this->_replaceWildcards($args);
            $message .= Mock::getExpectationLine(' at line [%d]');
            $this->_expected_args[strtolower($method)] =
                    new ParametersExpectation($args, $message);
        }
        
        /**
         *    Sets up an expected call with a set of
         *    expected parameters in that call. The
         *    expected call count will be adjusted if it
         *    is set too low to reach this call.
         *    @param integer $timing    Number of calls in the future at
         *                              which to test. Next call is 0.
         *    @param string $method     Method call to test.
         *    @param array $args        Expected parameters for the call
         *                              including wildcards.
         *    @param string $message    Overridden message.
         *    @access public
         */
        function expectArgumentsAt($timing, $method, $args, $message = '%s') {
            $this->_dieOnNoMethod($method, 'set expected arguments at time');
            $this->_checkArgumentsIsArray($args, 'set expected arguments at time');
            $args = $this->_replaceWildcards($args);
            if (! isset($this->_expected_args_at[$timing])) {
                $this->_expected_args_at[$timing] = array();
            }
            $method = strtolower($method);
            $message .= Mock::getExpectationLine(' at line [%d]');
            $this->_expected_args_at[$timing][$method] =
                    new ParametersExpectation($args, $message);
        }
        
        /**
         *    Sets an expectation for the number of times
         *    a method will be called. The tally method
         *    is used to check this.
         *    @param string $method        Method call to test.
         *    @param integer $count        Number of times it should
         *                                 have been called at tally.
         *    @param string $message       Overridden message.
         *    @access public
         */
        function expectCallCount($method, $count, $message = '%s') {
            $this->_dieOnNoMethod($method, 'set expected call count');
            $message .= Mock::getExpectationLine(' at line [%d]');
            $this->_expected_counts[strtolower($method)] =
                    new CallCountExpectation($method, $count, $message);
        }
        
        /**
         *    Sets the number of times a method may be called
         *    before a test failure is triggered.
         *    @param string $method        Method call to test.
         *    @param integer $count        Most number of times it should
         *                                 have been called.
         *    @param string $message       Overridden message.
         *    @access public
         */
        function expectMaximumCallCount($method, $count, $message = '%s') {
            $this->_dieOnNoMethod($method, 'set maximum call count');
            $message .= Mock::getExpectationLine(' at line [%d]');
            $this->_max_counts[strtolower($method)] = 
                    new MaximumCallCountExpectation($method, $count, $message);
        }
        
        /**
         *    Sets the number of times to call a method to prevent
         *    a failure on the tally.
         *    @param string $method      Method call to test.
         *    @param integer $count      Least number of times it should
         *                               have been called.
         *    @param string $message     Overridden message.
         *    @access public
         */
        function expectMinimumCallCount($method, $count, $message = '%s') {
            $this->_dieOnNoMethod($method, 'set minimum call count');
            $message .= Mock::getExpectationLine(' at line [%d]');
            $this->_expected_counts[strtolower($method)] =
                    new MinimumCallCountExpectation($method, $count, $message);
        }
        
        /**
         *    Convenience method for barring a method
         *    call.
         *    @param string $method        Method call to ban.
         *    @param string $message       Overridden message.
         *    @access public
         */
        function expectNever($method, $message = '%s') {
            $this->expectMaximumCallCount($method, 0, $message);
        }
        
        /**
         *    Convenience method for a single method
         *    call.
         *    @param string $method     Method call to track.
         *    @param array $args        Expected argument list or
         *                              false for any arguments.
         *    @param string $message    Overridden message.
         *    @access public
         */
        function expectOnce($method, $args = false, $message = '%s') {
            $this->expectCallCount($method, 1, $message);
            if ($args !== false) {
                $this->expectArguments($method, $args, $message);
            }
        }
        
        /**
         *    Convenience method for requiring a method
         *    call.
         *    @param string $method       Method call to track.
         *    @param array $args          Expected argument list or
         *                                false for any arguments.
         *    @param string $message      Overridden message.
         *    @access public
         */
        function expectAtLeastOnce($method, $args = false, $message = '%s') {
            $this->expectMinimumCallCount($method, 1, $message);
            if ($args !== false) {
                $this->expectArguments($method, $args, $message);
            }
        }
        
        /**
         *    Totals up the call counts and triggers a test
         *    assertion if a test is present for expected
         *    call counts.
         *    This method must be called explicitly for the call
         *    count assertions to be triggered.
         *    @access public
         */
        function tally() {
            foreach ($this->_expected_counts as $method => $expectation) {
                $this->_assertTrue(
                        $expectation->test($this->getCallCount($method)),
                        $expectation->overlayMessage($this->getCallCount($method)));
            }
            foreach ($this->_max_counts as $method => $expectation) {
                if ($expectation->test($this->getCallCount($method))) {
                    $this->_assertTrue(
                            true,
                            $expectation->overlayMessage($this->getCallCount($method)));
                }
            }
        }

        /**
         *    Returns the expected value for the method name
         *    and checks expectations. Will generate any
         *    test assertions as a result of expectations
         *    if there is a test present.
         *    @param string $method       Name of method to simulate.
         *    @param array $args          Arguments as an array.
         *    @return mixed               Stored return.
         *    @access private
         */
        function &_invoke($method, $args) {
            $method = strtolower($method);
            $step = $this->getCallCount($method);
            $this->_addCall($method, $args);
            $this->_checkExpectations($method, $args, $step);
            return $this->_getReturn($method, $args, $step);
        }
        
        /**
         *    Tests the arguments against expectations.
         *    @param string $method        Method to check.
         *    @param array $args           Argument list to match.
         *    @param integer $timing       The position of this call
         *                                 in the call history.
         *    @access private
         */
        function _checkExpectations($method, $args, $timing) {
            if (isset($this->_max_counts[$method])) {
                if (! $this->_max_counts[$method]->test($timing + 1)) {
                    $this->_assertTrue(
                            false,
                            $this->_max_counts[$method]->overlayMessage($timing + 1));
                }
            }
            if (isset($this->_expected_args_at[$timing][$method])) {
                $this->_assertTrue(
                        $this->_expected_args_at[$timing][$method]->test($args),
                        "Mock method [$method] at [$timing] -> " .
                                $this->_expected_args_at[$timing][$method]->overlayMessage($args));
            } elseif (isset($this->_expected_args[$method])) {
                $this->_assertTrue(
                        $this->_expected_args[$method]->test($args),
                        "Mock method [$method] -> " . $this->_expected_args[$method]->overlayMessage($args));
            }
        }
        
        /**
         *    Triggers an assertion on the held test case.
         *    Should be overridden when using another test
         *    framework other than the SimpleTest one if the
         *    assertion method has a different name.
         *    @param boolean $assertion     True will pass.
         *    @param string $message        Message that will go with
         *                                  the test event.
         *    @access protected
         */
        function _assertTrue($assertion, $message) {
            $test = &SimpleMock::injectTest($this->_test);
            $test->assertTrue($assertion, $message);
        }
        
        /**
         *    Stashes the test case for later recovery.
         *    @param SimpleTestCase $test    Test case.
         *    @return string                 Key to find it again.
         *    @access public
         *    @static
         */
        function registerTest(&$test) {
            $registry = &SimpleMock::_getRegistry();
            $registry[$class = get_class($test)] = &$test;
            return $class;
        }
        
        /**
         *    Resolves the dependency on the test case.
         *    @param string $class      Key to look up test case in.
         *    @return SimpleTestCase    Test case to send results to.
         *    @access public
         *    @static
         */
        function &injectTest($key) {
            $registry = &SimpleMock::_getRegistry();
            return $registry[$key];
        }
        
        /**
         *    Registry for test cases. The reason for this is
         *    to break the reference between the test cases and
         *    the mocks. It was leading to a fatal error due to
         *    recursive dependencies during comparisons. See
         *    http://bugs.php.net/bug.php?id=31449 for the PHP
         *    bug.
         *    @return array        List of references.
         *    @access private
         *    @static
         */
        function &_getRegistry() {
            static $registry;
            if (! isset($registry)) {
                $registry = array();
            }
            return $registry;
        }
    }
    
    /**
     *    Static methods only service class for code generation of
     *    server stubs.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class Stub {
        
        /**
         *    Factory for server stub classes.
         */
        function Stub() {
            trigger_error('Stub factory methods are class only.');
        }
        
        /**
         *    Clones a class' interface and creates a stub version
         *    that can have return values set.
         *    @param string $class        Class to clone.
         *    @param string $stub_class   New class name. Default is
         *                                the old name with "Stub"
         *                                prepended.
         *    @param array $methods       Additional methods to add beyond
         *                                those in the cloned class. Use this
         *                                to emulate the dynamic addition of
         *                                methods in the cloned class or when
         *                                the class hasn't been written yet.
         *    @static
         *    @access public
         */
        function generate($class, $stub_class = false, $methods = false) {
            if (! SimpleTestCompatibility::classExists($class)) {
                return false;
            }
            if (! $stub_class) {
                $stub_class = "Stub" . $class;
            }
            if (SimpleTestCompatibility::classExists($stub_class)) {
                return false;
            }
            return eval(Stub::_createClassCode(
                    $class,
                    $stub_class,
                    $methods ? $methods : array()) . " return true;");
        }
        
        /**
         *    The new server stub class code in string form.
         *    @param string $class           Class to clone.
         *    @param string $mock_class      New class name.
         *    @param array $methods          Additional methods.
         *    @static
         *    @access private
         */
        function _createClassCode($class, $stub_class, $methods) {
            $stub_base = SimpleTestOptions::getStubBaseClass();
            $code = "class $stub_class extends $stub_base {\n";
            $code .= "    function $stub_class(\$wildcard = MOCK_WILDCARD) {\n";
            $code .= "        \$this->$stub_base(\$wildcard);\n";
            $code .= "    }\n";
            $code .= Stub::_createHandlerCode($class, $stub_base, $methods);
            $code .= "}\n";
            return $code;
        }
        
        /**
         *    Creates code within a class to generate replaced
         *    methods. All methods call the _invoke() handler
         *    with the method name and the arguments in an
         *    array.
         *    @param string $class     Class to clone.
         *    @param string $base      Base mock/stub class with methods that
         *                             cannot be cloned. Otherwise you
         *                             would be stubbing the accessors used
         *                             to set the stubs.
         *    @param array $methods    Additional methods.
         *    @static
         *    @access private
         */
        function _createHandlerCode($class, $base, $methods) {
            $code = "";
            $methods = array_merge($methods, get_class_methods($class));
            foreach ($methods as $method) {
                if (Stub::_isSpecialMethod($method)) {
                    continue;
                }
                if (in_array($method, get_class_methods($base))) {
                    continue;
                }
                $code .= "    function &$method() {\n";
                $code .= "        \$args = func_get_args();\n";
                $code .= "        return \$this->_invoke(\"$method\", \$args);\n";
                $code .= "    }\n";
            }
            return $code;
        }
        
        /**
         *    Tests to see if a special PHP method is about to
         *    be stubbed by mistake.
         *    @param string $method    Method name.
         *    @return boolean          True if special.
         *    @access private
         *    @static
         */
        function _isSpecialMethod($method) {
            return in_array(
                    strtolower($method),
                    array('__construct', '__clone', '__get', '__set', '__call'));
        }
    }
    
    /**
     *    Static methods only service class for code generation of
     *    mock objects.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class Mock {
        
        /**
         *    Factory for mock object classes.
         *    @access public
         */
        function Mock() {
            trigger_error("Mock factory methods are class only.");
        }
        
        /**
         *    Clones a class' interface and creates a mock version
         *    that can have return values and expectations set.
         *    @param string $class         Class to clone.
         *    @param string $mock_class    New class name. Default is
         *                                 the old name with "Mock"
         *                                 prepended.
         *    @param array $methods        Additional methods to add beyond
         *                                 those in th cloned class. Use this
         *                                 to emulate the dynamic addition of
         *                                 methods in the cloned class or when
         *                                 the class hasn't been written yet.
         *    @static
         *    @access public
         */
        function generate($class, $mock_class = false, $methods = false) {
            if (! SimpleTestCompatibility::classExists($class)) {
                return false;
            }
            if (! $mock_class) {
                $mock_class = "Mock" . $class;
            }
            if (SimpleTestCompatibility::classExists($mock_class)) {
                return false;
            }
            return eval(Mock::_createClassCode(
                    $class,
                    $mock_class,
                    $methods ? $methods : array()) . " return true;");
        }
        
        /**
         *    Generates a version of a class with selected
         *    methods mocked only. Inherits the old class
         *    and chains the mock methods of an aggregated
         *    mock object.
         *    @param string $class            Class to clone.
         *    @param string $mock_class       New class name.
         *    @param array $methods           Methods to be overridden
         *                                    with mock versions.
         *    @static
         *    @access public
         */
        function generatePartial($class, $mock_class, $methods) {
            if (! SimpleTestCompatibility::classExists($class)) {
                return false;
            }
            if (SimpleTestCompatibility::classExists($mock_class)) {
                trigger_error("Partial mock class [$mock_class] already exists");
                return false;
            }
            return eval(Mock::_extendClassCode($class, $mock_class, $methods));
        }

        /**
         *    The new mock class code as a string.
         *    @param string $class           Class to clone.
         *    @param string $mock_class      New class name.
         *    @param array $methods          Additional methods.
         *    @return string                 Code for new mock class.
         *    @static
         *    @access private
         */
        function _createClassCode($class, $mock_class, $methods) {
            $mock_base = SimpleTestOptions::getMockBaseClass();
            $code = "class $mock_class extends $mock_base {\n";
            $code .= "    function $mock_class(&\$test, \$wildcard = MOCK_WILDCARD) {\n";
            $code .= "        \$this->$mock_base(\$test, \$wildcard);\n";
            $code .= "    }\n";
            $code .= Stub::_createHandlerCode($class, $mock_base, $methods);
            $code .= "}\n";
            return $code;
        }

        /**
         *    The extension class code as a string. The class
         *    composites a mock object and chains mocked methods
         *    to it.
         *    @param string $class         Class to extend.
         *    @param string $mock_class    New class name.
         *    @param array  $methods       Mocked methods.
         *    @return string               Code for a new class.
         *    @static
         *    @access private
         */
        function _extendClassCode($class, $mock_class, $methods) {
            $mock_base = SimpleTestOptions::getMockBaseClass();
            $code  = "class $mock_class extends $class {\n";
            $code .= "    var \$_mock;\n";
            $code .= Mock::_addMethodList($methods);
            $code .= "\n";
            $code .= "    function $mock_class(&\$test, \$wildcard = MOCK_WILDCARD) {\n";
            $code .= "        \$this->_mock = &new $mock_base(\$test, \$wildcard, false);\n";
            $code .= "    }\n";
            $code .= Mock::_chainMockReturns();
            $code .= Mock::_chainMockExpectations();
            $code .= Mock::_overrideMethods($methods);
            $code .= SimpleTestOptions::getPartialMockCode();
            $code .= "}\n";
            return $code;
        }
        
        /**
         *    Creates a list of mocked methods for error checking.
         *    @param array $methods       Mocked methods.
         *    @return string              Code for a method list.
         *    @access private
         */
        function _addMethodList($methods) {
            return "    var \$_mocked_methods = array('" . implode("', '", $methods) . "');\n";
        }
        
        /**
         *    Creates code to abandon the expectation if not mocked.
         *    @param string $alias       Parameter name of method name.
         *    @return string             Code for bail out.
         *    @access private
         */
        function _bailOutIfNotMocked($alias) {
            $code  = "        if (! in_array($alias, \$this->_mocked_methods)) {\n";
            $code .= "            trigger_error(\"Method [$alias] is not mocked\");\n";
            $code .= "            return;\n";
            $code .= "        }\n";
            return $code;
        }
        
        /**
         *    Creates source code for chaining to the composited
         *    mock object.
         *    @return string           Code for mock set up.
         *    @access private
         */
        function _chainMockReturns() {
            $code  = "    function setReturnValue(\$method, \$value, \$args = false) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->setReturnValue(\$method, \$value, \$args);\n";
            $code .= "    }\n";
            $code .= "    function setReturnValueAt(\$timing, \$method, \$value, \$args = false) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->setReturnValueAt(\$timing, \$method, \$value, \$args);\n";
            $code .= "    }\n";
            $code .= "    function setReturnReference(\$method, &\$ref, \$args = false) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->setReturnReference(\$method, \$ref, \$args);\n";
            $code .= "    }\n";
            $code .= "    function setReturnReferenceAt(\$timing, \$method, &\$ref, \$args = false) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->setReturnReferenceAt(\$timing, \$method, \$ref, \$args);\n";
            $code .= "    }\n";
            return $code;
        }
        
        /**
         *    Creates source code for chaining to an aggregated
         *    mock object.
         *    @return string                 Code for expectations.
         *    @access private
         */
        function _chainMockExpectations() {
            $code = "    function expectArguments(\$method, \$args = false) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->expectArguments(\$method, \$args);\n";
            $code .= "    }\n";
            $code .= "    function expectArgumentsAt(\$timing, \$method, \$args = false) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->expectArgumentsAt(\$timing, \$method, \$args);\n";
            $code .= "    }\n";
            $code .= "    function expectCallCount(\$method, \$count) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->expectCallCount(\$method, \$count);\n";
            $code .= "    }\n";
            $code .= "    function expectMaximumCallCount(\$method, \$count) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->expectMaximumCallCount(\$method, \$count);\n";
            $code .= "    }\n";
            $code .= "    function expectMinimumCallCount(\$method, \$count) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->expectMinimumCallCount(\$method, \$count);\n";
            $code .= "    }\n";
            $code .= "    function expectNever(\$method) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->expectNever(\$method);\n";
            $code .= "    }\n";
            $code .= "    function expectOnce(\$method, \$args = false) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->expectOnce(\$method, \$args);\n";
            $code .= "    }\n";
            $code .= "    function expectAtLeastOnce(\$method, \$args = false) {\n";
            $code .= Mock::_bailOutIfNotMocked("\$method");
            $code .= "        \$this->_mock->expectAtLeastOnce(\$method, \$args);\n";
            $code .= "    }\n";
            $code .= "    function tally() {\n";
            $code .= "        \$this->_mock->tally();\n";
            $code .= "    }\n";
            return $code;
        }
        
        /**
         *    Creates source code to override a list of methods
         *    with mock versions.
         *    @param array $methods    Methods to be overridden
         *                             with mock versions.
         *    @return string           Code for overridden chains.
         *    @access private
         */
        function _overrideMethods($methods) {
            $code = "";
            foreach ($methods as $method) {
                $code .= "    function &$method() {\n";
                $code .= "        \$args = func_get_args();\n";
                $code .= "        return \$this->_mock->_invoke(\"$method\", \$args);\n";
                $code .= "    }\n";
            }
            return $code;
        }
        
        /**
         *    Uses a stack trace to find the line of an assertion.
         *    @param string $format    String formatting.
         *    @param array $stack      Stack frames top most first. Only
         *                             needed if not using the PHP
         *                             backtrace function.
         *    @return string           Line number of first expect*
         *                             method embedded in format string.
         *    @access public
         *    @static
         */
        function getExpectationLine($format = '%d', $stack = false) {
            if ($stack === false) {
                $stack = SimpleTestCompatibility::getStackTrace();
            }
            return SimpleDumper::getFormattedAssertionLine($stack, $format, 'expect');
        }
    }
?>