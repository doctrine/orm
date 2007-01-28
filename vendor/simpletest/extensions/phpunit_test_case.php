<?php
    /**
     *	adapter for SimpleTest to use PHPUnit test cases
     *	@package	SimpleTest
     *	@subpackage Extensions
     *	@version	$Id: phpunit_test_case.php,v 1.3 2004/04/23 03:11:56 jsweat Exp $
     */
    
    /**#@+
     * include SimpleTest files
     */
    require_once dirname(__FILE__).DIRECTORY_SEPARATOR
            .'..'.DIRECTORY_SEPARATOR . 'unit_tester.php';
    require_once dirname(__FILE__).DIRECTORY_SEPARATOR
            .'..'.DIRECTORY_SEPARATOR . 'expectation.php';
    /**#@-*/
    
    /**
     *    Adapter for sourceforge PHPUnit test case to allow
     *    legacy test cases to be used with SimpleTest.
     *    @package		SimpleTest
     *    @subpackage	 Extensions
     */
    class TestCase extends SimpleTestCase {
        
        /**
         *    Constructor. Sets the test name.
         *    @param $label        Test name to display.
         *    @public
         */
        function TestCase($label) {
            $this->SimpleTestCase($label);
        }
        
        /**
         *    Sends pass if the test condition resolves true,
         *    a fail otherwise.
         *    @param $condition      Condition to test true.
         *    @param $message        Message to display.
         *    @public
         */
        function assert($condition, $message = false) {
            parent::assertTrue($condition, $message);
        }
        
        /**
         *    Will test straight equality if set to loose
         *    typing, or identity if not.
         *    @param $first          First value.
         *    @param $second         Comparison value.
         *    @param $message        Message to display.
         *    @public
         */
        function assertEquals($first, $second, $message = false) {
            $this->assertExpectation(
                    new EqualExpectation($first),
                    $second,
                    $message);
        }
        
        /**
         *    Will test straight equality if set to loose
         *    typing, or identity if not.
         *    @param $first          First value.
         *    @param $second         Comparison value.
         *    @param $message        Message to display.
         *    @public
         */
        function assertEqualsMultilineStrings($first, $second, $message = false) {
            $this->assertExpectation(
                    new EqualExpectation($first),
                    $second,
                    $message);
        }                             
        
        /**
         *    Tests a regex match.
         *    @param $pattern        Regex to match.
         *    @param $subject        String to search in.
         *    @param $message        Message to display.
         *    @public
         */
        function assertRegexp($pattern, $subject, $message = false) {
            $this->assertExpectation(
                    new WantedPatternExpectation($pattern),
                    $subject,
                    $message);
        }
        
        /**
         *    Sends an error which we interpret as a fail
         *    with a different message for compatibility.
         *    @param $message        Message to display.
         *    @public
         */
        function error($message) {
            parent::assertTrue(false, "Error triggered [$message]");
        }
         
        /**
         *    Accessor for name.
         *    @public
         */
       function name() {
            return $this->getLabel();
        }
    }
?>
