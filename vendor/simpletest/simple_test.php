<?php
    /**
     *	Base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id: simple_test.php,v 1.89 2005/02/18 19:25:05 lastcraft Exp $
     */

    /**#@+
     * Includes SimpleTest files and defined the root constant
     * for dependent libraries.
     */
    require_once(dirname(__FILE__) . '/errors.php');
    require_once(dirname(__FILE__) . '/options.php');
    require_once(dirname(__FILE__) . '/runner.php');
    require_once(dirname(__FILE__) . '/scorer.php');
    require_once(dirname(__FILE__) . '/expectation.php');
    require_once(dirname(__FILE__) . '/dumper.php');
    if (! defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', dirname(__FILE__) . '/');
    }
    /**#@-*/

    /**
     *    Basic test case. This is the smallest unit of a test
     *    suite. It searches for
     *    all methods that start with the the string "test" and
     *    runs them. Working test cases extend this class.
	 *    @package		SimpleTest
	 *    @subpackage	UnitTester
     */
    class SimpleTestCase {
        var $_label;
        var $_runner;

        /**
         *    Sets up the test with no display.
         *    @param string $label    If no test name is given then
         *                            the class name is used.
         *    @access public
         */
        function SimpleTestCase($label = false) {
            $this->_label = $label ? $label : get_class($this);
            $this->_runner = false;
        }

        /**
         *    Accessor for the test name for subclasses.
         *    @return string           Name of the test.
         *    @access public
         */
        function getLabel() {
            return $this->_label;
        }

        /**
         *    Used to invoke the single tests.
         *    @return SimpleInvoker        Individual test runner.
         *    @access public
         */
        function createInvoker() {
            return new SimpleErrorTrappingInvoker(new SimpleInvoker($this));
        }

        /**
         *    Can modify the incoming reporter so as to run
         *    the tests differently. This version simply
         *    passes it straight through.
         *    @param SimpleReporter $reporter    Incoming observer.
         *    @return SimpleReporter
         *    @access protected
         */
        function _createRunner($reporter) {
            return new SimpleRunner($this, $reporter);
        }

        /**
         *    Uses reflection to run every method within itself
         *    starting with the string "test" unless a method
         *    is specified.
         *    @param SimpleReporter $reporter    Current test reporter.
         *    @access public
         */
        function run(&$reporter) {
            $reporter->paintCaseStart($this->getLabel());
            $this->_runner = $this->_createRunner($reporter);
            $this->_runner->run();
            $reporter->paintCaseEnd($this->getLabel());
            return $reporter->getStatus();
        }

        /**
         *    Sets up unit test wide variables at the start
         *    of each test method. To be overridden in
         *    actual user test cases.
         *    @access public
         */
        function setUp() {
        }

        /**
         *    Clears the data set in the setUp() method call.
         *    To be overridden by the user in actual user test cases.
         *    @access public
         */
        function tearDown() {
        }

        /**
         *    Sends a pass event with a message.
         *    @param string $message        Message to send.
         *    @access public
         */
        function pass($message = "Pass") {
            $this->_runner->paintPass($message . $this->getAssertionLine(' at line [%d]'));
        }

        /**
         *    Sends a fail event with a message.
         *    @param string $message        Message to send.
         *    @access public
         */
        function fail($message = "Fail") {
            $this->_runner->paintFail($message . $this->getAssertionLine(' at line [%d]'));
        }

        /**
         *    Formats a PHP error and dispatches it to the
         *    runner.
         *    @param integer $severity  PHP error code.
         *    @param string $message    Text of error.
         *    @param string $file       File error occoured in.
         *    @param integer $line      Line number of error.
         *    @param hash $globals      PHP super global arrays.
         *    @access public
         */
        function error($severity, $message, $file, $line, $globals) {
            $severity = SimpleErrorQueue::getSeverityAsString($severity);
            $this->_runner->paintError(
                    "Unexpected PHP error [$message] severity [$severity] in [$file] line [$line]");
        }

        /**
         *    Sends a user defined event to the test runner.
         *    This is for small scale extension where
         *    both the test case and either the runner or
         *    display are subclassed.
         *    @param string $type       Type of event.
         *    @param mixed $payload     Object or message to deliver.
         *    @access public
         */
        function signal($type, &$payload) {
            $this->_runner->paintSignal($type, $payload);
        }

        /**
         *    Cancels any outstanding errors.
         *    @access public
         */
        function swallowErrors() {
            $queue = &SimpleErrorQueue::instance();
            $queue->clear();
        }

        /**
         *    Runs an expectation directly, for extending the
         *    tests with new expectation classes.
         *    @param SimpleExpectation $expectation  Expectation subclass.
         *    @param mixed $test_value               Value to compare.
         *    @param string $message                 Message to display.
         *    @return boolean                        True on pass
         *    @access public
         */
        function assertExpectation(&$expectation, $test_value, $message = '%s') {
            return $this->assertTrue(
                    $expectation->test($test_value),
                    sprintf($message, $expectation->overlayMessage($test_value)));
        }

        /**
         *    Called from within the test methods to register
         *    passes and failures.
         *    @param boolean $result    Pass on true.
         *    @param string $message    Message to display describing
         *                              the test state.
         *    @return boolean           True on pass
         *    @access public
         */
        function assertTrue($result, $message = false) {
            if (! $message) {
                $message = 'True assertion got ' . ($result ? 'True' : 'False');
            }
            if ($result) {
                $this->pass($message);
                return true;
            } else {
                $this->fail($message);
                return false;
            }
        }

        /**
         *    Will be true on false and vice versa. False
         *    is the PHP definition of false, so that null,
         *    empty strings, zero and an empty array all count
         *    as false.
         *    @param boolean $result    Pass on false.
         *    @param string $message    Message to display.
         *    @return boolean           True on pass
         *    @access public
         */
        function assertFalse($result, $message = false) {
            if (! $message) {
                $message = 'False assertion got ' . ($result ? 'True' : 'False');
            }
            return $this->assertTrue(! $result, $message);
        }

        /**
         *    Uses a stack trace to find the line of an assertion.
         *    @param string $format    String formatting.
         *    @param array $stack      Stack frames top most first. Only
         *                             needed if not using the PHP
         *                             backtrace function.
         *    @return string           Line number of first assert*
         *                             method embedded in format string.
         *    @access public
         */
        function getAssertionLine($format = '%d', $stack = false) {
            if ($stack === false) {
                $stack = SimpleTestCompatibility::getStackTrace();
            }
            return SimpleDumper::getFormattedAssertionLine($stack, $format);
        }

        /**
         *    Sends a formatted dump of a variable to the
         *    test suite for those emergency debugging
         *    situations.
         *    @param mixed $variable    Variable to display.
         *    @param string $message    Message to display.
         *    @return mixed             The original variable.
         *    @access public
         */
        function dump($variable, $message = false) {
            $formatted = SimpleDumper::dump($variable);
            if ($message) {
                $formatted = $message . "\n" . $formatted;
            }
            $this->_runner->paintFormattedMessage($formatted);
            return $variable;
        }

        /**
         *    Dispatches a text message straight to the
         *    test suite. Useful for status bar displays.
         *    @param string $message        Message to show.
         *    @access public
         */
        function sendMessage($message) {
            $this->_runner->PaintMessage($message);
        }

        /**
         *    Accessor for the number of subtests.
         *    @return integer           Number of test cases.
         *    @access public
         *    @static
         */
        function getSize() {
            return 1;
        }
    }

    /**
     *    This is a composite test class for combining
     *    test cases and other RunnableTest classes into
     *    a group test.
	 *    @package		SimpleTest
	 *    @subpackage	UnitTester
     */
    class GroupTest {
        var $_label;
        var $_test_cases;
        var $_old_track_errors;
        var $_xdebug_is_enabled;

        /**
         *    Sets the name of the test suite.
         *    @param string $label    Name sent at the start and end
         *                            of the test.
         *    @access public
         */
        function GroupTest($label) {
            $this->_label = $label;
            $this->_test_cases = array();
            $this->_old_track_errors = ini_get('track_errors');
            $this->_xdebug_is_enabled = function_exists('xdebug_is_enabled') ?
                    xdebug_is_enabled() : false;
        }

        /**
         *    Accessor for the test name for subclasses.
         *    @return string           Name of the test.
         *    @access public
         */
        function getLabel() {
            return $this->_label;
        }

        /**
         *    Adds a test into the suite. Can be either a group
         *    test or some other unit test.
         *    @param SimpleTestCase $test_case  Suite or individual test
         *                                      case implementing the
         *                                      runnable test interface.
         *    @access public
         */
        function addTestCase(&$test_case) {
            $this->_test_cases[] = &$test_case;
        }

        /**
         *    Adds a test into the suite by class name. The class will
         *    be instantiated as needed.
         *    @param SimpleTestCase $test_case  Suite or individual test
         *                                      case implementing the
         *                                      runnable test interface.
         *    @access public
         */
        function addTestClass($class) {
            $this->_test_cases[] = $class;
        }

        /**
         *    Builds a group test from a library of test cases.
         *    The new group is composed into this one.
         *    @param string $test_file        File name of library with
         *                                    test case classes.
         *    @access public
         */
        function addTestFile($test_file) {
            $existing_classes = get_declared_classes();
            if ($error = $this->_requireWithError($test_file)) {
                $this->addTestCase(new BadGroupTest($test_file, $error));
                return;
            }
            $classes = $this->_selectRunnableTests($existing_classes, get_declared_classes());
            if (count($classes) == 0) {
                $this->addTestCase(new BadGroupTest($test_file, 'No new test cases'));
                return;
            }
            $this->addTestCase($this->_createGroupFromClasses($test_file, $classes));
        }

        /**
         *    Requires a source file recording any syntax errors.
         *    @param string $file        File name to require in.
         *    @return string/boolean     An error message on failure or false
         *                               if no errors.
         *    @access private
         */
        function _requireWithError($file) {
            $this->_enableErrorReporting();
            include($file);
            $error = isset($php_errormsg) ? $php_errormsg : false;
            $this->_disableErrorReporting();
            $self_inflicted = array(
                    'Assigning the return value of new by reference is deprecated',
                    'var: Deprecated. Please use the public/private/protected modifiers');
            if (in_array($error, $self_inflicted)) {
                return false;
            }
            return $error;
        }

        /**
         *    Sets up detection of parse errors. Note that XDebug
         *    interferes with this and has to be disabled. This is
         *    to make sure the correct error code is returned
         *    from unattended scripts.
         *    @access private
         */
        function _enableErrorReporting() {
            if ($this->_xdebug_is_enabled) {
                xdebug_disable();
            }
            ini_set('track_errors', true);
        }

        /**
         *    Resets detection of parse errors to their old values.
         *    This is to make sure the correct error code is returned
         *    from unattended scripts.
         *    @access private
         */
        function _disableErrorReporting() {
            ini_set('track_errors', $this->_old_track_errors);
            if ($this->_xdebug_is_enabled) {
                xdebug_enable();
            }
        }

        /**
         *    Calculates the incoming test cases from a before
         *    and after list of loaded classes.
         *    @param array $existing_classes   Classes before require().
         *    @param array $new_classes        Classes after require().
         *    @return array                    New classes which are test
         *                                     cases that shouldn't be ignored.
         *    @access private
         */
        function _selectRunnableTests($existing_classes, $new_classes) {
            $classes = array();
            foreach ($new_classes as $class) {
                if (in_array($class, $existing_classes)) {
                    continue;
                }
                if (! $this->_isTestCase($class)) {
                    continue;
                }
                $classes[] = $class;
            }
            return $classes;
        }

        /**
         *    Builds a group test from a class list.
         *    @param string $title       Title of new group.
         *    @param array $classes      Test classes.
         *    @return GroupTest          Group loaded with the new
         *                               test cases.
         *    @access private
         */
        function _createGroupFromClasses($title, $classes) {
            $group = new GroupTest($title);
            foreach ($classes as $class) {
                if (SimpleTestOptions::isIgnored($class)) {
                    continue;
                }
                $group->addTestClass($class);
            }
            return $group;
        }

        /**
         *    Test to see if a class is derived from the
         *    TestCase class.
         *    @param string $class            Class name.
         *    @access private
         */
        function _isTestCase($class) {
            while ($class = get_parent_class($class)) {
                $class = strtolower($class);
                if ($class == "simpletestcase" || $class == "grouptest") {
                    return true;
                }
            }
            return false;
        }

        /**
         *    Invokes run() on all of the held test cases, instantiating
         *    them if necessary.
         *    @param SimpleReporter $reporter    Current test reporter.
         *    @access public
         */
        function run(&$reporter) {
            $reporter->paintGroupStart($this->getLabel(), $this->getSize());
            for ($i = 0, $count = count($this->_test_cases); $i < $count; $i++) {
                if (is_string($this->_test_cases[$i])) {
                    $class = $this->_test_cases[$i];
                    $test = &new $class();
                    $test->run($reporter);
                } else {
                    $this->_test_cases[$i]->run($reporter);
                }
            }
            $reporter->paintGroupEnd($this->getLabel());
            return $reporter->getStatus();
        }

        /**
         *    Number of contained test cases.
         *    @return integer     Total count of cases in the group.
         *    @access public
         */
        function getSize() {
            $count = 0;
            foreach ($this->_test_cases as $case) {
                if (is_string($case)) {
                    $count++;
                } else {
                    $count += $case->getSize();
                }
            }
            return $count;
        }
    }

    /**
     *    This is a failing group test for when a test suite hasn't
     *    loaded properly.
	 *    @package		SimpleTest
	 *    @subpackage	UnitTester
     */
    class BadGroupTest {
        var $_label;
        var $_error;

        /**
         *    Sets the name of the test suite and error message.
         *    @param string $label    Name sent at the start and end
         *                            of the test.
         *    @access public
         */
        function BadGroupTest($label, $error) {
            $this->_label = $label;
            $this->_error = $error;
        }

        /**
         *    Accessor for the test name for subclasses.
         *    @return string           Name of the test.
         *    @access public
         */
        function getLabel() {
            return $this->_label;
        }

        /**
         *    Sends a single error to the reporter.
         *    @param SimpleReporter $reporter    Current test reporter.
         *    @access public
         */
        function run(&$reporter) {
            $reporter->paintGroupStart($this->getLabel(), $this->getSize());
            $reporter->paintFail('Bad GroupTest [' . $this->getLabel() .
                    '] with error [' . $this->_error . ']');
            $reporter->paintGroupEnd($this->getLabel());
            return $reporter->getStatus();
        }

        /**
         *    Number of contained test cases. Always zero.
         *    @return integer     Total count of cases in the group.
         *    @access public
         */
        function getSize() {
            return 0;
        }
    }
?>
