<?php
    /**
     *	Base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id: runner.php,v 1.29 2005/01/11 04:03:46 lastcraft Exp $
     */
    
    /**#@+
     * Includes SimpleTest files and defined the root constant
     * for dependent libraries.
     */
    require_once(dirname(__FILE__) . '/errors.php');
    require_once(dirname(__FILE__) . '/options.php');
    require_once(dirname(__FILE__) . '/scorer.php');
    require_once(dirname(__FILE__) . '/expectation.php');
    require_once(dirname(__FILE__) . '/dumper.php');
    if (! defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', dirname(__FILE__) . '/');
    }
    /**#@-*/
    
    /**
     *    This is called by the class runner to run a
     *    single test method. Will also run the setUp()
     *    and tearDown() methods.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class SimpleInvoker {
        var $_test_case;
        
        /**
         *    Stashes the test case for later.
         *    @param SimpleTestCase $test_case  Test case to run.
         */
        function SimpleInvoker(&$test_case) {
            $this->_test_case = &$test_case;
        }
        
        /**
         *    Accessor for test case being run.
         *    @return SimpleTestCase    Test case.
         *    @access public
         */
        function &getTestCase() {
            return $this->_test_case;
        }
        
        /**
         *    Invokes a test method and buffered with setUp()
         *    and tearDown() calls.
         *    @param string $method    Test method to call.
         *    @access public
         */
        function invoke($method) {
            $this->_test_case->setUp();
            $this->_test_case->$method();
            $this->_test_case->tearDown();
        }
    }
    
    /**
     *    Do nothing decorator. Just passes the invocation
     *    straight through.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class SimpleInvokerDecorator {
        var $_invoker;
        
        /**
         *    Stores the invoker to wrap.
         *    @param SimpleInvoker $invoker  Test method runner.
         */
        function SimpleInvokerDecorator(&$invoker) {
            $this->_invoker = &$invoker;
        }
        
        /**
         *    Accessor for test case being run.
         *    @return SimpleTestCase    Test case.
         *    @access public
         */
        function &getTestCase() {
            return $this->_invoker->getTestCase();
        }
        
        /**
         *    Invokes a test method and buffered with setUp()
         *    and tearDown() calls.
         *    @param string $method    Test method to call.
         *    @access public
         */
        function invoke($method) {
            $this->_invoker->invoke($method);
        }
    }

    /**
     *    Extension that traps errors into an error queue.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class SimpleErrorTrappingInvoker extends SimpleInvokerDecorator {
        
        /**
        /**
         *    Stores the invoker to wrap.
         *    @param SimpleInvoker $invoker  Test method runner.
         */
        function SimpleErrorTrappingInvoker(&$invoker) {
            $this->SimpleInvokerDecorator($invoker);
        }
        
        /**
         *    Invokes a test method and dispatches any
         *    untrapped errors. Called back from
         *    the visiting runner.
         *    @param string $method    Test method to call.
         *    @access public
         */
        function invoke($method) {
            set_error_handler('simpleTestErrorHandler');
            parent::invoke($method);
            $queue = &SimpleErrorQueue::instance();
            while (list($severity, $message, $file, $line, $globals) = $queue->extract()) {
                $test_case = &$this->getTestCase();
                $test_case->error($severity, $message, $file, $line, $globals);
            }
            restore_error_handler();
        }
    }

    /**
     *    The standard runner. Will run every method starting
     *    with test Basically the
     *    Mediator pattern.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class SimpleRunner {
        var $_test_case;
        var $_scorer;
        
        /**
         *    Takes in the test case and reporter to mediate between.
         *    @param SimpleTestCase $test_case  Test case to run.
         *    @param SimpleScorer $scorer       Reporter to receive events.
         */
        function SimpleRunner(&$test_case, &$scorer) {
            $this->_test_case = &$test_case;
            $this->_scorer = &$scorer;
        }
        
        /**
         *    Accessor for test case being run.
         *    @return SimpleTestCase    Test case.
         *    @access public
         */
        function &getTestCase() {
            return $this->_test_case;
        }
        
        /**
         *    Runs the test methods in the test case.
         *    @param SimpleTest $test_case    Test case to run test on.
         *    @param string $method           Name of test method.
         *    @access public
         */
        function run() {
            $methods = get_class_methods(get_class($this->_test_case));
            $invoker = $this->_test_case->createInvoker();
            foreach ($methods as $method) {
                if (! $this->_isTest($method)) {
                    continue;
                }
                if ($this->_isConstructor($method)) {
                    continue;
                }
                $this->_scorer->paintMethodStart($method);
                if ($this->_scorer->shouldInvoke($this->_test_case->getLabel(), $method)) {
                    $invoker->invoke($method);
                }
                $this->_scorer->paintMethodEnd($method);
            }
        }
        
        /**
         *    Tests to see if the method is the constructor and
         *    so should be ignored.
         *    @param string $method        Method name to try.
         *    @return boolean              True if constructor.
         *    @access protected
         */
        function _isConstructor($method) {
            return SimpleTestCompatibility::isA(
                    $this->_test_case,
                    strtolower($method));
        }
        
        /**
         *    Tests to see if the method is a test that should
         *    be run. Currently any method that starts with 'test'
         *    is a candidate.
         *    @param string $method        Method name to try.
         *    @return boolean              True if test method.
         *    @access protected
         */
        function _isTest($method) {
            return strtolower(substr($method, 0, 4)) == 'test';
        }

        /**
         *    Paints the start of a test method.
         *    @param string $test_name     Name of test or other label.
         *    @access public
         */
        function paintMethodStart($test_name) {
            $this->_scorer->paintMethodStart($test_name);
        }
        
        /**
         *    Paints the end of a test method.
         *    @param string $test_name     Name of test or other label.
         *    @access public
         */
        function paintMethodEnd($test_name) {
            $this->_scorer->paintMethodEnd($test_name);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $message        Message is ignored.
         *    @access public
         */
        function paintPass($message) {
            $this->_scorer->paintPass($message);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $message        Message is ignored.
         *    @access public
         */
        function paintFail($message) {
            $this->_scorer->paintFail($message);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $message    Text of error formatted by
         *                              the test case.
         *    @access public
         */
        function paintError($message) {
            $this->_scorer->paintError($message);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param Exception $exception     Object thrown.
         *    @access public
         */
        function paintException($exception) {
            $this->_scorer->paintException($exception);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $message        Text to display.
         *    @access public
         */
        function paintMessage($message) {
            $this->_scorer->paintMessage($message);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $message        Text to display.
         *    @access public
         */
        function paintFormattedMessage($message) {
            $this->_scorer->paintFormattedMessage($message);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $type        Event type as text.
         *    @param mixed $payload      Message or object.
         *    @return boolean            Should return false if this
         *                               type of signal should fail the
         *                               test suite.
         *    @access public
         */
        function paintSignal($type, &$payload) {
            $this->_scorer->paintSignal($type, $payload);
        }
    }
?>
