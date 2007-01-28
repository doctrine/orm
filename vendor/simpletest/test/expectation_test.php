<?php
    // $Id: expectation_test.php,v 1.19 2005/01/13 01:31:57 lastcraft Exp $
    require_once(dirname(__FILE__).DIRECTORY_SEPARATOR . '../expectation.php');

    class TestOfEquality extends UnitTestCase {
        
        function testBoolean() {
            $is_true = &new EqualExpectation(true);
            $this->assertTrue($is_true->test(true));
            $this->assertFalse($is_true->test(false));
            $this->assertWantedPattern(
                    '/equal expectation.*?boolean: true/i',
                    $is_true->testMessage(true));
            $this->assertWantedPattern(
                    '/fails.*?boolean.*?boolean/i',
                    $is_true->testMessage(false));
        }
        
        function testStringMatch() {
            $hello = &new EqualExpectation("Hello");
            $this->assertTrue($hello->test("Hello"));
            $this->assertFalse($hello->test("Goodbye"));
            $this->assertWantedPattern('/Equal expectation.*?Hello/', $hello->testMessage("Hello"));
            $this->assertWantedPattern('/fails/', $hello->testMessage("Goodbye"));
            $this->assertWantedPattern('/fails.*?goodbye/i', $hello->testMessage("Goodbye"));
        }
        
        function testStringPosition() {
            $comparisons = array(
                    "ab" => 2,
                    "a" => 1,
                    "abcz" => 3,
                    "abz" => 2,
                    "az" => 1,
                    "z" => 0);
            $str = &new EqualExpectation("abc");
            foreach ($comparisons as $compare => $position) {
                $this->assertWantedPattern(
                        "/at character $position/",
                        $str->testMessage($compare));
            }
            $str = &new EqualExpectation("abcd");
            foreach ($comparisons as $compare => $position) {
                $this->assertWantedPattern(
                        "/at character $position/",
                        $str->testMessage($compare));
            }
        }
        
        function testInteger() {
            $fifteen = &new EqualExpectation(15);
            $this->assertTrue($fifteen->test(15));
            $this->assertFalse($fifteen->test(14));
            $this->assertWantedPattern(
                    '/equal expectation.*?15/i',
                    $fifteen->testMessage(15));
            $this->assertWantedPattern(
                    '/fails.*?15.*?14/i',
                    $fifteen->testMessage(14));
        }
        
        function testFloat() {
            $pi = &new EqualExpectation(3.14);
            $this->assertTrue($pi->test(3.14));
            $this->assertFalse($pi->test(3.15));
            $this->assertWantedPattern(
                    '/float.*?3\.14/i',
                    $pi->testMessage(3.14));
            $this->assertWantedPattern(
                    '/fails.*?3\.14.*?3\.15/i',
                    $pi->testMessage(3.15));
        }
        
        function testArray() {
            $colours = &new EqualExpectation(array("r", "g", "b"));
            $this->assertTrue($colours->test(array("r", "g", "b")));
            $this->assertFalse($colours->test(array("g", "b", "r")));
            $this->assertEqual(
                    $colours->testMessage(array("r", "g", "b")),
                    "Equal expectation [Array: 3 items]");
            $this->assertWantedPattern('/fails/', $colours->testMessage(array("r", "g", "z")));
            $this->assertWantedPattern(
                    '/\[2\] at character 0/',
                    $colours->testMessage(array("r", "g", "z")));
            $this->assertWantedPattern(
                    '/key.*? does not match/',
                    $colours->testMessage(array("r", "g")));
            $this->assertWantedPattern(
                    '/key.*? does not match/',
                    $colours->testMessage(array("r", "g", "b", "z")));
        }
        
        function testHash() {
            $is_blue = &new EqualExpectation(array("r" => 0, "g" => 0, "b" => 255));
            $this->assertTrue($is_blue->test(array("r" => 0, "g" => 0, "b" => 255)));
            $this->assertFalse($is_blue->test(array("r" => 0, "g" => 255, "b" => 0)));
            $this->assertWantedPattern(
                    '/array.*?3 items/i',
                    $is_blue->testMessage(array("r" => 0, "g" => 0, "b" => 255)));
            $this->assertWantedPattern(
                    '/fails.*?\[b\]/',
                    $is_blue->testMessage(array("r" => 0, "g" => 0, "b" => 254)));
        }
        
        function testNestedHash() {
            $tree = &new EqualExpectation(array(
                    "a" => 1,
                    "b" => array(
                            "c" => 2,
                            "d" => "Three")));
            $this->assertWantedPattern(
                    '/member.*?\[b\].*?\[d\].*?at character 5/',
                    $tree->testMessage(array(
                        "a" => 1,
                        "b" => array(
                                "c" => 2,
                                "d" => "Threeish"))));
        }
        
        function testHashWithOutOfOrderKeysShouldStillMatch() {
            $any_order = &new EqualExpectation(array('a' => 1, 'b' => 2));
            $this->assertTrue($any_order->test(array('b' => 2, 'a' => 1)));
        }
    }
    
    class TestOfInequality extends UnitTestCase {
        
        function testStringMismatch() {
            $not_hello = &new NotEqualExpectation("Hello");
            $this->assertTrue($not_hello->test("Goodbye"));
            $this->assertFalse($not_hello->test("Hello"));
            $this->assertWantedPattern(
                    '/at character 0/',
                    $not_hello->testMessage("Goodbye"));
            $this->assertWantedPattern(
                    '/matches/',
                    $not_hello->testMessage("Hello"));
        }
    }
    
    class RecursiveNasty {
        var $_me;
        
        function RecursiveNasty() {
            $this->_me = $this;
        }
    }
    
    class TestOfIdentity extends UnitTestCase {
        
        function testType() {
            $string = &new IdenticalExpectation("37");
            $this->assertTrue($string->test("37"));
            $this->assertFalse($string->test(37));
            $this->assertFalse($string->test("38"));
            $this->assertWantedPattern(
                    '/identical.*?string.*?37/i',
                    $string->testMessage("37"));
            $this->assertWantedPattern(
                    '/fails.*?37/',
                    $string->testMessage(37));
            $this->assertWantedPattern(
                    '/at character 1/',
                    $string->testMessage("38"));
        }
        
        function _testNastyPhp5Bug() {
            $this->assertFalse(new RecursiveNasty() != new RecursiveNasty());
        }
        
        function _testReallyHorribleRecursiveStructure() {
            $hopeful = &new IdenticalExpectation(new RecursiveNasty());
            $this->assertTrue($hopeful->test(new RecursiveNasty()));
        }
    }
    
    class TestOfNonIdentity extends UnitTestCase {
        
        function testType() {
            $string = &new NotIdenticalExpectation("37");
            $this->assertTrue($string->test("38"));
            $this->assertTrue($string->test(37));
            $this->assertFalse($string->test("37"));
            $this->assertWantedPattern(
                    '/at character 1/',
                    $string->testMessage("38"));
            $this->assertWantedPattern(
                    '/passes.*?type/',
                    $string->testMessage(37));
        }
    }
    
    class TestOfPatterns extends UnitTestCase {
        
        function testWanted() {
            $pattern = &new WantedPatternExpectation('/hello/i');
            $this->assertTrue($pattern->test("Hello world"));
            $this->assertFalse($pattern->test("Goodbye world"));
        }
        
        function testUnwanted() {
            $pattern = &new UnwantedPatternExpectation('/hello/i');
            $this->assertFalse($pattern->test("Hello world"));
            $this->assertTrue($pattern->test("Goodbye world"));
        }
    }
    
    class ExpectedMethodTarget {
        function hasThisMethod() {}
    }

    class TestOfMethodExistence extends UnitTestCase {
        
        function testHasMethod() {
            $instance = &new ExpectedMethodTarget();
            $expectation = &new MethodExistsExpectation('hasThisMethod');
            $this->assertTrue($expectation->test($instance));
            $expectation = &new MethodExistsExpectation('doesNotHaveThisMethod');
            $this->assertFalse($expectation->test($instance));
        }
    }
    
    class TestOfIsA extends UnitTestCase {
        
        function testString() {
            $expectation = &new IsAExpectation('string');
            $this->assertTrue($expectation->test('Hello'));
            $this->assertFalse($expectation->test(5));
        }
        
        function testBoolean() {
            $expectation = &new IsAExpectation('boolean');
            $this->assertTrue($expectation->test(true));
            $this->assertFalse($expectation->test(1));
        }
        
        function testBool() {
            $expectation = &new IsAExpectation('bool');
            $this->assertTrue($expectation->test(true));
            $this->assertFalse($expectation->test(1));
        }
        
        function testDouble() {
            $expectation = &new IsAExpectation('double');
            $this->assertTrue($expectation->test(5.0));
            $this->assertFalse($expectation->test(5));
        }
        
        function testFloat() {
            $expectation = &new IsAExpectation('float');
            $this->assertTrue($expectation->test(5.0));
            $this->assertFalse($expectation->test(5));
        }
        
        function testReal() {
            $expectation = &new IsAExpectation('real');
            $this->assertTrue($expectation->test(5.0));
            $this->assertFalse($expectation->test(5));
        }
        
        function testInteger() {
            $expectation = &new IsAExpectation('integer');
            $this->assertTrue($expectation->test(5));
            $this->assertFalse($expectation->test(5.0));
        }
        
        function testInt() {
            $expectation = &new IsAExpectation('int');
            $this->assertTrue($expectation->test(5));
            $this->assertFalse($expectation->test(5.0));
        }
    }
    
    class TestOfNotA extends UnitTestCase {
        
        function testString() {
            $expectation = &new NotAExpectation('string');
            $this->assertFalse($expectation->test('Hello'));
            $this->assertTrue($expectation->test(5));
        }
    }
?>