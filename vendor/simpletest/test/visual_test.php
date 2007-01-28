<?php
    // $Id: visual_test.php,v 1.29 2004/11/22 19:20:00 lastcraft Exp $

    // NOTE:
    // Some of these tests are designed to fail! Do not be alarmed.
    //                         ----------------

    // The following tests are a bit hacky. Whilst Kent Beck tried to
    // build a unit tester with a unit tester I am not that brave.
    // Instead I have just hacked together odd test scripts until
    // I have enough of a tester to procede more formally.
    //
    // The proper tests start in all_tests.php
    require_once('../unit_tester.php');
    require_once('../shell_tester.php');
    require_once('../mock_objects.php');
    require_once('../reporter.php');
    require_once('../xml.php');

    class TestDisplayClass {
        var $_a;

        function TestDisplayClass($a) {
            $this->_a = $a;
        }
    }

    class TestOfUnitTestCaseOutput extends UnitTestCase {

        function testOfResults() {
            $this->pass('Pass');
            $this->fail('Fail');        // Fail.
        }

        function testTrue() {
            $this->assertTrue(true);
            $this->assertTrue(false);        // Fail.
        }

        function testFalse() {
            $this->assertFalse(true);        // Fail.
            $this->assertFalse(false);
        }

        function testExpectation() {
            $expectation = &new EqualExpectation(25, 'My expectation message: %s');
            $this->assertExpectation($expectation, 25, 'My assert message : %s');
            $this->assertExpectation($expectation, 24, 'My assert message : %s');        // Fail.
        }

        function testNull() {
            $this->assertNull(null, "%s -> Pass");
            $this->assertNull(false, "%s -> Fail");        // Fail.
            $this->assertNotNull(null, "%s -> Fail");        // Fail.
            $this->assertNotNull(false, "%s -> Pass");
        }

        function testType() {
            $this->assertIsA("hello", "string", "%s -> Pass");
            $this->assertIsA(14, "string", "%s -> Fail");        // Fail.
            $this->assertIsA($this, "TestOfUnitTestCaseOutput", "%s -> Pass");
            $this->assertIsA($this, "UnitTestCase", "%s -> Pass");
            $this->assertIsA(14, "TestOfUnitTestCaseOutput", "%s -> Fail");        // Fail.
            $this->assertIsA($this, "TestReporter", "%s -> Fail");        // Fail.
        }

        function testTypeEquality() {
            $this->assertEqual("0", 0, "%s -> Pass");
            $this->assertNotEqual("0", 0, "%s -> Fail");        // Fail.
        }

        function testNullEquality() {
            $this->assertEqual(null, 1, "%s -> Fail");        // Fail.
            $this->assertNotEqual(null, 1, "%s -> Pass");
            $this->assertEqual(1, null, "%s -> Fail");        // Fail.
            $this->assertNotEqual(1, null, "%s -> Pass");
        }

        function testIntegerEquality() {
            $this->assertEqual(1, 2, "%s -> Fail");        // Fail.
            $this->assertNotEqual(1, 2, "%s -> Pass");
        }

        function testStringEquality() {
            $this->assertEqual("a", "a", "%s -> Pass");
            $this->assertNotEqual("a", "a", "%s -> Fail");    // Fail.
            $this->assertEqual("aa", "ab", "%s -> Fail");        // Fail.
            $this->assertNotEqual("aa", "ab", "%s -> Pass");
        }

        function testHashEquality() {
            $this->assertEqual(array("a" => "A", "b" => "B"), array("b" => "B", "a" => "A"), "%s -> Pass");
            $this->assertEqual(array("a" => "A", "b" => "B"), array("b" => "B", "a" => "Z"), "%s -> Pass");
        }

        function testStringIdentity() {
            $a = "fred";
            $b = $a;
            $this->assertIdentical($a, $b, "%s -> Pass");
            $this->assertNotIdentical($a, $b, "%s -> Fail");       // Fail.
        }

        function testTypeIdentity() {
            $a = "0";
            $b = 0;
            $this->assertIdentical($a, $b, "%s -> Fail");        // Fail.
            $this->assertNotIdentical($a, $b, "%s -> Pass");
        }

        function testNullIdentity() {
            $this->assertIdentical(null, 1, "%s -> Fail");        // Fail.
            $this->assertNotIdentical(null, 1, "%s -> Pass");
            $this->assertIdentical(1, null, "%s -> Fail");        // Fail.
            $this->assertNotIdentical(1, null, "%s -> Pass");
        }

        function testHashIdentity() {
            $this->assertIdentical(array("a" => "A", "b" => "B"), array("b" => "B", "a" => "A"), "%s -> fail");        // Fail.
        }

        function testObjectEquality() {
            $this->assertEqual(new TestDisplayClass(4), new TestDisplayClass(4), "%s -> Pass");
            $this->assertNotEqual(new TestDisplayClass(4), new TestDisplayClass(4), "%s -> Fail");    // Fail.
            $this->assertEqual(new TestDisplayClass(4), new TestDisplayClass(5), "%s -> Fail");        // Fail.
            $this->assertNotEqual(new TestDisplayClass(4), new TestDisplayClass(5), "%s -> Pass");
        }

        function testObjectIndentity() {
            $this->assertIdentical(new TestDisplayClass(false), new TestDisplayClass(false), "%s -> Pass");
            $this->assertNotIdentical(new TestDisplayClass(false), new TestDisplayClass(false), "%s -> Fail");    // Fail.
            $this->assertIdentical(new TestDisplayClass(false), new TestDisplayClass(0), "%s -> Fail");        // Fail.
            $this->assertNotIdentical(new TestDisplayClass(false), new TestDisplayClass(0), "%s -> Pass");
        }

        function testReference() {
            $a = "fred";
            $b = &$a;
            $this->assertReference($a, $b, "%s -> Pass");
            $this->assertCopy($a, $b, "%s -> Fail");        // Fail.
            $c = "Hello";
            $this->assertReference($a, $c, "%s -> Fail");        // Fail.
            $this->assertCopy($a, $c, "%s -> Pass");
        }

        function testPatterns() {
            $this->assertWantedPattern('/hello/i', "Hello there", "%s -> Pass");
            $this->assertNoUnwantedPattern('/hello/', "Hello there", "%s -> Pass");
            $this->assertWantedPattern('/hello/', "Hello there", "%s -> Fail");            // Fail.
            $this->assertNoUnwantedPattern('/hello/i', "Hello there", "%s -> Fail");      // Fail.
        }

        function testLongStrings() {
            $text = "";
            for ($i = 0; $i < 10; $i++) {
                $text .= "0123456789";
            }
            $this->assertEqual($text, $text);
            $this->assertEqual($text . $text, $text . "a" . $text);        // Fail.
        }

        function testErrorDisplay() {
            trigger_error('Default');        // Exception.
            trigger_error('Error', E_USER_ERROR);        // Exception.
            trigger_error('Warning', E_USER_WARNING);        // Exception.
            trigger_error('Notice', E_USER_NOTICE);        // Exception.
        }

        function testErrorTrap() {
            $this->assertNoErrors("%s -> Pass");
            $this->assertError();        // Fail.
            trigger_error('Error 1');
            $this->assertNoErrors("%s -> Fail");        // Fail.
            $this->assertError();
            $this->assertNoErrors("%s -> Pass at end");
        }

        function testErrorText() {
            trigger_error('Error 2');
            $this->assertError('Error 2', "%s -> Pass");
            trigger_error('Error 3');
            $this->assertError('Error 2', "%s -> Fail");        // Fail.
        }

        function testErrorPatterns() {
            trigger_error('Error 2');
            $this->assertErrorPattern('/Error 2/', "%s -> Pass");
            trigger_error('Error 3');
            $this->assertErrorPattern('/Error 2/', "%s -> Fail");        // Fail.
        }

        function testDumping() {
            $this->dump(array("Hello"), "Displaying a variable");
        }

        function testSignal() {
            $fred = "signal as a string";
            $this->signal("Signal", $fred);        // Signal.
        }
    }

    class Dummy {
        function Dummy() {
        }

        function a() {
        }
    }
    Mock::generate('Dummy');

    class TestOfMockObjectsOutput extends UnitTestCase {

        function testCallCounts() {
            $dummy = &new MockDummy($this);
            $dummy->expectCallCount('a', 1, 'My message: %s');
            $dummy->a();
            $dummy->tally();
            $dummy->a();
            $dummy->tally();
        }

        function testMinimumCallCounts() {
            $dummy = &new MockDummy($this);
            $dummy->expectMinimumCallCount('a', 2, 'My message: %s');
            $dummy->a();
            $dummy->tally();
            $dummy->a();
            $dummy->tally();
        }

        function testEmptyMatching() {
            $dummy = &new MockDummy($this);
            $dummy->expectArguments('a', array());
            $dummy->a();
            $dummy->a(null);        // Fail.
        }

        function testEmptyMatchingWithCustomMessage() {
            $dummy = &new MockDummy($this);
            $dummy->expectArguments('a', array(), 'My expectation message: %s');
            $dummy->a();
            $dummy->a(null);        // Fail.
        }

        function testNullMatching() {
            $dummy = &new MockDummy($this);
            $dummy->expectArguments('a', array(null));
            $dummy->a(null);
            $dummy->a();        // Fail.
        }

        function testBooleanMatching() {
            $dummy = &new MockDummy($this);
            $dummy->expectArguments('a', array(true, false));
            $dummy->a(true, false);
            $dummy->a(true, true);        // Fail.
        }

        function testIntegerMatching() {
            $dummy = &new MockDummy($this);
            $dummy->expectArguments('a', array(32, 33));
            $dummy->a(32, 33);
            $dummy->a(32, 34);        // Fail.
        }

        function testFloatMatching() {
            $dummy = &new MockDummy($this);
            $dummy->expectArguments('a', array(3.2, 3.3));
            $dummy->a(3.2, 3.3);
            $dummy->a(3.2, 3.4);        // Fail.
        }

        function testStringMatching() {
            $dummy = &new MockDummy($this);
            $dummy->expectArguments('a', array('32', '33'));
            $dummy->a('32', '33');
            $dummy->a('32', '34');        // Fail.
        }

        function testEmptyMatchingWithCustomExpectationMessage() {
            $dummy = &new MockDummy($this);
            $dummy->expectArguments(
                    'a',
                    array(new EqualExpectation('A', 'My part expectation message: %s')),
                    'My expectation message: %s');
            $dummy->a('A');
            $dummy->a('B');        // Fail.
        }

        function testArrayMatching() {
            $dummy = &new MockDummy($this);
            $dummy->expectArguments('a', array(array(32), array(33)));
            $dummy->a(array(32), array(33));
            $dummy->a(array(32), array('33'));        // Fail.
        }

        function testObjectMatching() {
            $a = new Dummy();
            $a->a = 'a';
            $b = new Dummy();
            $b->b = 'b';
            $dummy = &new MockDummy($this);
            $dummy->expectArguments('a', array($a, $b));
            $dummy->a($a, $b);
            $dummy->a($a, $a);        // Fail.
        }

        function testBigList() {
            $dummy = &new MockDummy($this);
            $dummy->expectArguments('a', array(false, 0, 1, 1.0));
            $dummy->a(false, 0, 1, 1.0);
            $dummy->a(true, false, 2, 2.0);        // Fail.
        }
    }

    class TestOfPastBugs extends UnitTestCase {

        function testMixedTypes() {
            $this->assertEqual(array(), null, "%s -> Pass");
            $this->assertIdentical(array(), null, "%s -> Fail");    // Fail.
        }

        function testMockWildcards() {
            $dummy = &new MockDummy($this);
            $dummy->expectArguments('a', array('*', array(33)));
            $dummy->a(array(32), array(33));
            $dummy->a(array(32), array('33'));        // Fail.
        }
    }

    class TestOfVisualShell extends ShellTestCase {

        function testDump() {
            $this->execute('ls');
            $this->dumpOutput();
            $this->execute('dir');
            $this->dumpOutput();
        }

        function testDumpOfList() {
            $this->execute('ls');
            $this->dump($this->getOutputAsList());
        }
    }

    class AllOutputReporter extends HtmlReporter {

        function _getCss() {
            return parent::_getCss() . ' .pass { color: darkgreen; }';
        }

        function paintPass($message) {
            parent::paintPass($message);
            print "<span class=\"pass\">Pass</span>: ";
            $breadcrumb = $this->getTestList();
            array_shift($breadcrumb);
            print implode(" -&gt; ", $breadcrumb);
            print " -&gt; " . htmlentities($message) . "<br />\n";
        }

        function paintSignal($type, &$payload) {
            print "<span class=\"fail\">$type</span>: ";
            $breadcrumb = $this->getTestList();
            array_shift($breadcrumb);
            print implode(" -&gt; ", $breadcrumb);
            print " -&gt; " . htmlentities(serialize($payload)) . "<br />\n";
        }
    }

    $test = &new GroupTest("Visual test with 49 passes, 49 fails and 4 exceptions");
    $test->addTestCase(new TestOfUnitTestCaseOutput());
    $test->addTestCase(new TestOfMockObjectsOutput());
    $test->addTestCase(new TestOfPastBugs());
    $test->addTestCase(new TestOfVisualShell());

    if (isset($_GET['xml']) || in_array('xml', (isset($argv) ? $argv : array()))) {
        $reporter = &new XmlReporter();
    } elseif(SimpleReporter::inCli()) {
        $reporter = &new TextReporter();
    } else {
        $reporter = &new AllOutputReporter();
    }
    if (isset($_GET['dry']) || in_array('dry', (isset($argv) ? $argv : array()))) {
        $reporter->makeDry();
    }
    exit ($test->run($reporter) ? 0 : 1);
?>