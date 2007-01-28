<?php
    // $Id: simple_mock_test.php,v 1.41 2005/01/23 22:20:52 lastcraft Exp $
    
    require_once(dirname(__FILE__) . '/../expectation.php');
    
    class TestOfWildcardExpectation extends UnitTestCase {
        
        function testSimpleInteger() {
            $expectation = new WildcardExpectation();
            $this->assertTrue($expectation->test(33));
            $this->assertWantedPattern(
                    '/matches.*33/i',
                    $expectation->testMessage(33));
        }
    }
    
    class TestOfParametersExpectation extends UnitTestCase {
        
        function testEmptyMatch() {
            $expectation = new ParametersExpectation(array());
            $this->assertTrue($expectation->test(array()));
            $this->assertFalse($expectation->test(array(33)));
        }
        
        function testSingleMatch() {
            $expectation = new ParametersExpectation(array(0));
            $this->assertFalse($expectation->test(array(1)));
            $this->assertTrue($expectation->test(array(0)));
        }
        
        function testAnyMatch() {
            $expectation = new ParametersExpectation(false);
            $this->assertTrue($expectation->test(array()));
            $this->assertTrue($expectation->test(array(1, 2)));
        }
        
        function testMissingParameter() {
            $expectation = new ParametersExpectation(array(0));
            $this->assertFalse($expectation->test(array()));
        }
        
        function testNullParameter() {
            $expectation = new ParametersExpectation(array(null));
            $this->assertTrue($expectation->test(array(null)));
            $this->assertFalse($expectation->test(array()));
        }
        
        function testWildcardExpectations() {
            $expectation = new ParametersExpectation(array(new WildcardExpectation()));
            $this->assertFalse($expectation->test(array()));
            $this->assertIdentical($expectation->test(array(null)), true);
            $this->assertIdentical($expectation->test(array(13)), true);
        }
        
        function testOtherExpectations() {
            $expectation = new ParametersExpectation(
                    array(new WantedPatternExpectation('/hello/i')));
            $this->assertFalse($expectation->test(array('Goodbye')));
            $this->assertTrue($expectation->test(array('hello')));
            $this->assertTrue($expectation->test(array('Hello')));
        }
        
        function testIdentityOnly() {
            $expectation = new ParametersExpectation(array("0"));
            $this->assertFalse($expectation->test(array(0)));
            $this->assertTrue($expectation->test(array("0")));
        }
        
        function testLongList() {
            $expectation = new ParametersExpectation(
                    array("0", 0, new WildcardExpectation(), false));
            $this->assertTrue($expectation->test(array("0", 0, 37, false)));
            $this->assertFalse($expectation->test(array("0", 0, 37, true)));
            $this->assertFalse($expectation->test(array("0", 0, 37)));
        }
    }
    
    class TestOfCallMap extends UnitTestCase {
        
        function testEmpty() {
            $map = new CallMap();
            $this->assertFalse($map->isMatch("any", array()));
            $this->assertNull($map->findFirstMatch("any", array()));
        }
        
        function testExactValue() {
            $map = new CallMap();
            $map->addValue(array(0), "Fred");
            $map->addValue(array(1), "Jim");
            $map->addValue(array("1"), "Tom");
            $this->assertTrue($map->isMatch(array(0)));
            $this->assertEqual($map->findFirstMatch(array(0)), "Fred");
            $this->assertTrue($map->isMatch(array(1)));
            $this->assertEqual($map->findFirstMatch(array(1)), "Jim");
            $this->assertEqual($map->findFirstMatch(array("1")), "Tom");
        }
        
        function testExactReference() {
            $map = new CallMap();
            $ref = "Fred";
            $map->addReference(array(0), $ref);
            $this->assertEqual($map->findFirstMatch(array(0)), "Fred");
            $ref2 = &$map->findFirstMatch(array(0));
            $this->assertReference($ref2, $ref);
        }
        
        function testWildcard() {
            $map = new CallMap();
            $map->addValue(array(new WildcardExpectation(), 1, 3), "Fred");
            $this->assertTrue($map->isMatch(array(2, 1, 3)));
            $this->assertEqual($map->findFirstMatch(array(2, 1, 3)), "Fred");
        }
        
        function testAllWildcard() {
            $map = new CallMap();
            $this->assertFalse($map->isMatch(array(2, 1, 3)));
            $map->addValue("", "Fred");
            $this->assertTrue($map->isMatch(array(2, 1, 3)));
            $this->assertEqual($map->findFirstMatch(array(2, 1, 3)), "Fred");
        }
        
        function testOrdering() {
            $map = new CallMap();
            $map->addValue(array(1, 2), "1, 2");
            $map->addValue(array(1, 3), "1, 3");
            $map->addValue(array(1), "1");
            $map->addValue(array(1, 4), "1, 4");
            $map->addValue(array(new WildcardExpectation()), "Any");
            $map->addValue(array(2), "2");
            $map->addValue("", "Default");
            $map->addValue(array(), "None");
            $this->assertEqual($map->findFirstMatch(array(1, 2)), "1, 2");
            $this->assertEqual($map->findFirstMatch(array(1, 3)), "1, 3");
            $this->assertEqual($map->findFirstMatch(array(1, 4)), "1, 4");
            $this->assertEqual($map->findFirstMatch(array(1)), "1");
            $this->assertEqual($map->findFirstMatch(array(2)), "Any");
            $this->assertEqual($map->findFirstMatch(array(3)), "Any");
            $this->assertEqual($map->findFirstMatch(array()), "Default");
        }
    }
    
    class Dummy {
        function Dummy() {
        }
        
        function aMethod($parameter) {
            return $parameter;
        }
        
        function anotherMethod() {
            return true;
        }
    }
    
    Stub::generate("Dummy");
    Stub::generate("Dummy", "AnotherStubDummy");
    Stub::generate("Dummy", "StubDummyWithExtraMethods", array("extraMethod"));
    
    class SpecialSimpleStub extends SimpleStub {
        function SpecialSimpleStub($wildcard) {
            $this->SimpleStub($wildcard);
        }
    }
    SimpleTestOptions::setStubBaseClass("SpecialSimpleStub");
    Stub::generate("Dummy", "SpecialStubDummy");
    SimpleTestOptions::setStubBaseClass("SimpleStub");
    
    class TestOfStubGeneration extends UnitTestCase {
        
        function testCloning() {
            $stub = &new StubDummy();
            $this->assertTrue(method_exists($stub, "aMethod"));
            $this->assertNull($stub->aMethod());
        }
        
        function testCloningWithExtraMethod() {
            $stub = &new StubDummyWithExtraMethods();
            $this->assertTrue(method_exists($stub, "extraMethod"));
        }
        
        function testCloningWithChosenClassName() {
            $stub = &new AnotherStubDummy();
            $this->assertTrue(method_exists($stub, "aMethod"));
        }
        
        function testCloningWithDifferentBaseClass() {
            $stub = &new SpecialStubDummy();
            $this->assertIsA($stub, "SpecialSimpleStub");
            $this->assertTrue(method_exists($stub, "aMethod"));
        }
    }
    
    class TestOfServerStubReturns extends UnitTestCase {
        
        function testDefaultReturn() {
            $stub = &new StubDummy();
            $stub->setReturnValue("aMethod", "aaa");
            $this->assertIdentical($stub->aMethod(), "aaa");
            $this->assertIdentical($stub->aMethod(), "aaa");
        }
        
        function testParameteredReturn() {
            $stub = &new StubDummy();
            $stub->setReturnValue("aMethod", "aaa", array(1, 2, 3));
            $this->assertNull($stub->aMethod());
            $this->assertIdentical($stub->aMethod(1, 2, 3), "aaa");
        }
        
        function testReferenceReturned() {
            $stub = &new StubDummy();
            $object = new Dummy();
            $stub->setReturnReference("aMethod", $object, array(1, 2, 3));
            $this->assertReference($stub->aMethod(1, 2, 3), $object);
        }
        
        function testWildcardReturn() {
            $stub = &new StubDummy("wild");
            $stub->setReturnValue("aMethod", "aaa", array(1, "wild", 3));
            $this->assertIdentical($stub->aMethod(1, "something", 3), "aaa");
            $this->assertIdentical($stub->aMethod(1, "anything", 3), "aaa");
        }
        
        function testAllWildcardReturn() {
            $stub = &new StubDummy("wild");
            $stub->setReturnValue("aMethod", "aaa");
            $this->assertIdentical($stub->aMethod(1, 2, 3), "aaa");
            $this->assertIdentical($stub->aMethod(), "aaa");
        }
        
        function testCallCount() {
            $stub = &new StubDummy();
            $this->assertEqual($stub->getCallCount("aMethod"), 0);
            $stub->aMethod();
            $this->assertEqual($stub->getCallCount("aMethod"), 1);
            $stub->aMethod();
            $this->assertEqual($stub->getCallCount("aMethod"), 2);
        }
        
        function testMultipleMethods() {
            $stub = &new StubDummy();
            $stub->setReturnValue("aMethod", 100, array(1));
            $stub->setReturnValue("aMethod", 200, array(2));
            $stub->setReturnValue("anotherMethod", 10, array(1));
            $stub->setReturnValue("anotherMethod", 20, array(2));
            $this->assertIdentical($stub->aMethod(1), 100);
            $this->assertIdentical($stub->anotherMethod(1), 10);
            $this->assertIdentical($stub->aMethod(2), 200);
            $this->assertIdentical($stub->anotherMethod(2), 20);
        }
        
        function testReturnSequence() {
            $stub = &new StubDummy();
            $stub->setReturnValueAt(0, "aMethod", "aaa");
            $stub->setReturnValueAt(1, "aMethod", "bbb");
            $stub->setReturnValueAt(3, "aMethod", "ddd");
            $this->assertIdentical($stub->aMethod(), "aaa");
            $this->assertIdentical($stub->aMethod(), "bbb");
            $this->assertNull($stub->aMethod());
            $this->assertIdentical($stub->aMethod(), "ddd");
        }
        
        function testReturnReferenceSequence() {
            $stub = &new StubDummy();
            $object = new Dummy();
            $stub->setReturnReferenceAt(1, "aMethod", $object);
            $this->assertNull($stub->aMethod());
            $this->assertReference($stub->aMethod(), $object);
            $this->assertNull($stub->aMethod());
        }
        
        function testComplicatedReturnSequence() {
            $stub = &new StubDummy("wild");
            $object = new Dummy();
            $stub->setReturnValueAt(1, "aMethod", "aaa", array("a"));
            $stub->setReturnValueAt(1, "aMethod", "bbb");
            $stub->setReturnReferenceAt(2, "aMethod", $object, array("wild", 2));
            $stub->setReturnValueAt(2, "aMethod", "value", array("wild", 3));
            $stub->setReturnValue("aMethod", 3, array(3));
            $this->assertNull($stub->aMethod());
            $this->assertEqual($stub->aMethod("a"), "aaa");
            $this->assertReference($stub->aMethod(1, 2), $object);
            $this->assertEqual($stub->aMethod(3), 3);
            $this->assertNull($stub->aMethod());
        }
        
        function testMultipleMethodSequences() {
            $stub = &new StubDummy();
            $stub->setReturnValueAt(0, "aMethod", "aaa");
            $stub->setReturnValueAt(1, "aMethod", "bbb");
            $stub->setReturnValueAt(0, "anotherMethod", "ccc");
            $stub->setReturnValueAt(1, "anotherMethod", "ddd");
            $this->assertIdentical($stub->aMethod(), "aaa");
            $this->assertIdentical($stub->anotherMethod(), "ccc");
            $this->assertIdentical($stub->aMethod(), "bbb");
            $this->assertIdentical($stub->anotherMethod(), "ddd");
        }
        
        function testSequenceFallback() {
            $stub = &new StubDummy();
            $stub->setReturnValueAt(0, "aMethod", "aaa", array('a'));
            $stub->setReturnValueAt(1, "aMethod", "bbb", array('a'));
            $stub->setReturnValue("aMethod", "AAA");
            $this->assertIdentical($stub->aMethod('a'), "aaa");
            $this->assertIdentical($stub->aMethod('b'), "AAA");
        }
        
        function testMethodInterference() {
            $stub = &new StubDummy();
            $stub->setReturnValueAt(0, "anotherMethod", "aaa");
            $stub->setReturnValue("aMethod", "AAA");
            $this->assertIdentical($stub->aMethod(), "AAA");
            $this->assertIdentical($stub->anotherMethod(), "aaa");
        }
    }
    
    Mock::generate("Dummy");
    Mock::generate("Dummy", "AnotherMockDummy");
    Mock::generate("Dummy", "MockDummyWithExtraMethods", array("extraMethod"));
    
    class SpecialSimpleMock extends SimpleMock {
        function SpecialSimpleMock(&$test, $wildcard) {
            $this->SimpleMock($test, $wildcard);
        }
    }
    SimpleTestOptions::setMockBaseClass("SpecialSimpleMock");
    Mock::generate("Dummy", "SpecialMockDummy");
    SimpleTestOptions::setMockBaseClass("SimpleMock");
    
    class TestOfMockGeneration extends UnitTestCase {
        
        function testCloning() {
            $mock = &new MockDummy($this);
            $this->assertTrue(method_exists($mock, "aMethod"));
            $this->assertNull($mock->aMethod());
        }
        
        function testCloningWithExtraMethod() {
            $mock = &new MockDummyWithExtraMethods($this);
            $this->assertTrue(method_exists($mock, "extraMethod"));
        }
        
        function testCloningWithChosenClassName() {
            $mock = &new AnotherMockDummy($this);
            $this->assertTrue(method_exists($mock, "aMethod"));
        }
        
        function testCloningWithDifferentBaseClass() {
            $mock = &new SpecialMockDummy($this);
            $this->assertIsA($mock, "SpecialSimpleMock");
            $this->assertTrue(method_exists($mock, "aMethod"));
        }
    }
    
    class TestOfMockReturns extends UnitTestCase {
        
        function testNoUnitTesterSetThrowsError() {
            $mock = &new MockDummy();
            $this->assertErrorPattern('/missing argument/i');
            $this->assertErrorPattern('/no unit tester/i');
        }
        
        function testParameteredReturn() {
            $mock = &new MockDummy($this);
            $mock->setReturnValue("aMethod", "aaa", array(1, 2, 3));
            $this->assertNull($mock->aMethod());
            $this->assertIdentical($mock->aMethod(1, 2, 3), "aaa");
        }
        
        function testReferenceReturned() {
            $mock = &new MockDummy($this);
            $object = new Dummy();
            $mock->setReturnReference("aMethod", $object, array(1, 2, 3));
            $this->assertReference($mock->aMethod(1, 2, 3), $object);
        }
        
        function testWildcardReturn() {
            $mock = &new MockDummy($this, "wild");
            $mock->setReturnValue("aMethod", "aaa", array(1, "wild", 3));
            $this->assertIdentical($mock->aMethod(1, "something", 3), "aaa");
            $this->assertIdentical($mock->aMethod(1, "anything", 3), "aaa");
        }
        
        function testPatternMatchReturn() {
            $mock = &new MockDummy($this);
            $mock->setReturnValue(
                    "aMethod",
                    "aaa",
                    array(new wantedPatternExpectation('/hello/i')));
            $this->assertIdentical($mock->aMethod('Hello'), "aaa");
            $this->assertNull($mock->aMethod('Goodbye'));
        }
        
        function testCallCount() {
            $mock = &new MockDummy($this);
            $this->assertEqual($mock->getCallCount("aMethod"), 0);
            $mock->aMethod();
            $this->assertEqual($mock->getCallCount("aMethod"), 1);
            $mock->aMethod();
            $this->assertEqual($mock->getCallCount("aMethod"), 2);
        }
        
        function testReturnReferenceSequence() {
            $mock = &new MockDummy($this);
            $object = new Dummy();
            $mock->setReturnReferenceAt(1, "aMethod", $object);
            $this->assertNull($mock->aMethod());
            $this->assertReference($mock->aMethod(), $object);
            $this->assertNull($mock->aMethod());
            $this->swallowErrors();
        }
    }
    
    Mock::generate("SimpleTestCase");
    
    class TestOfMockTally extends UnitTestCase {
        
        function testZeroCallCount() {
            $mock = &new MockDummy($this);
            $mock->expectCallCount("aMethod", 0);
            $mock->tally();
        }
        
        function testExpectedCallCount() {
            $mock = &new MockDummy($this);
            $mock->expectCallCount("aMethod", 2);
            $mock->aMethod();
            $mock->aMethod();
            $mock->tally();
        }
    }
    
    class TestOfMockExpectations extends UnitTestCase {
        var $_test;
        
        function TestOfMockExpectations() {
            $this->UnitTestCase();
        }
        
        function setUp() {
            $this->_test = &new MockSimpleTestCase($this);
        }
        
        function tearDown() {
            $this->_test->tally();
        }
        
        function testSettingExpectationOnNonMethodThrowsError() {
            $mock = &new MockDummy($this);
            $mock->expectMaximumCallCount("aMissingMethod", 2);
            $this->assertError();
        }
        
        function testMaxCallsDetectsOverrun() {
            $this->_test->expectOnce("assertTrue", array(false, '*'));
            $mock = &new MockDummy($this->_test);
            $mock->expectMaximumCallCount("aMethod", 2);
            $mock->aMethod();
            $mock->aMethod();
            $mock->aMethod();
        }
        
        function testTallyOnMaxCallsSendsPassOnUnderrun() {
            $this->_test->expectOnce("assertTrue", array(true, '*'));
            $mock = &new MockDummy($this->_test);
            $mock->expectMaximumCallCount("aMethod", 2);
            $mock->aMethod();
            $mock->aMethod();
            $mock->tally();
        }
        
        function testExpectNeverDetectsOverrun() {
            $this->_test->expectOnce("assertTrue", array(false, '*'));
            $mock = &new MockDummy($this->_test);
            $mock->expectNever("aMethod");
            $mock->aMethod();
        }
        
        function testTallyOnExpectNeverSendsPassOnUnderrun() {
            $this->_test->expectOnce("assertTrue", array(true, '*'));
            $mock = &new MockDummy($this->_test);
            $mock->expectNever("aMethod");
            $mock->tally();
        }
        
        function testMinCalls() {
            $this->_test->expectOnce("assertTrue", array(true, '*'));
            $mock = &new MockDummy($this->_test);
            $mock->expectMinimumCallCount("aMethod", 2);
            $mock->aMethod();
            $mock->aMethod();
            $mock->tally();
        }
        
        function testFailedNever() {
            $this->_test->expectOnce("assertTrue", array(false, '*'));
            $mock = &new MockDummy($this->_test);
            $mock->expectNever("aMethod");
            $mock->aMethod();
        }
        
        function testUnderOnce() {
            $this->_test->expectOnce("assertTrue", array(false, '*'));
            $mock = &new MockDummy($this->_test);
            $mock->expectOnce("aMethod");
            $mock->tally();
        }
        
        function testOverOnce() {
            $this->_test->expectOnce("assertTrue", array(false, '*'));
            $mock = &new MockDummy($this->_test);
            $mock->expectOnce("aMethod");
            $mock->aMethod();
            $mock->aMethod();
            $mock->tally();
            $this->swallowErrors();
        }
        
        function testUnderAtLeastOnce() {
            $this->_test->expectOnce("assertTrue", array(false, '*'));
            $mock = &new MockDummy($this->_test);
            $mock->expectAtLeastOnce("aMethod");
            $mock->tally();
        }
        
        function testZeroArguments() {
            $mock = &new MockDummy($this);
            $mock->expectArguments("aMethod", array());
            $mock->aMethod();
        }
        
        function testExpectedArguments() {
            $mock = &new MockDummy($this);
            $mock->expectArguments("aMethod", array(1, 2, 3));
            $mock->aMethod(1, 2, 3);
        }
        
        function testFailedArguments() {
            $this->_test->expectOnce("assertTrue", array(false, "*"));
            $mock = &new MockDummy($this->_test);
            $mock->expectArguments("aMethod", array("this"));
            $mock->aMethod("that");
        }
        
        function testWildcardArguments() {
            $mock = &new MockDummy($this, "wild");
            $mock->expectArguments("aMethod", array("wild", 123, "wild"));
            $mock->aMethod(100, 123, 101);
        }
        
        function testSpecificSequence() {
            $mock = &new MockDummy($this);
            $mock->expectArgumentsAt(1, "aMethod", array(1, 2, 3));
            $mock->expectArgumentsAt(2, "aMethod", array("Hello"));
            $mock->aMethod();
            $mock->aMethod(1, 2, 3);
            $mock->aMethod("Hello");
            $mock->aMethod();
        }
        
        function testFailedSequence() {
            $this->_test->expectArguments("assertTrue", array(false, "*"));
            $this->_test->expectCallCount("assertTrue", 2);
            $mock = &new MockDummy($this->_test);
            $mock->expectArgumentsAt(0, "aMethod", array(1, 2, 3));
            $mock->expectArgumentsAt(1, "aMethod", array("Hello"));
            $mock->aMethod(1, 2);
            $mock->aMethod("Goodbye");
        }
        
        function testBadArgParameter() {
            $mock = &new MockDummy($this);
            $mock->expectArguments("aMethod", "foo");
            $this->assertErrorPattern('/\$args.*not an array/i');
            $mock->aMethod();
            $mock->tally();
       }
    }
    
    class TestOfMockComparisons extends UnitTestCase {
        
        function testTestCaseRegistry() {
            $test = &new MockSimpleTestCase($this);
            $class = SimpleMock::registerTest($test);
            $this->assertReference($test, SimpleMock::injectTest($class));
        }
        
        function testEqualComparisonOfMocksDoesNotCrash() {
            $expectation = &new EqualExpectation(new MockDummy($this));
            $this->assertTrue($expectation->test(new MockDummy($this), true));
        }
        
        function testIdenticalComparisonOfMocksDoesNotCrash() {
            $expectation = &new IdenticalExpectation(new MockDummy($this));
            $this->assertTrue($expectation->test(new MockDummy($this)));
        }
    }
    
    SimpleTestOptions::addPartialMockCode('function sayHello() { return "Hello"; }');
    Mock::generatePartial("Dummy", "TestDummy", array("anotherMethod"));
    SimpleTestOptions::addPartialMockCode();
    
    class TestOfPartialMocks extends UnitTestCase {
        
        function testMethodReplacement() {
            $mock = &new TestDummy($this);
            $this->assertEqual($mock->aMethod(99), 99);
            $this->assertNull($mock->anotherMethod());
        }
        
        function testSettingReturns() {
            $mock = &new TestDummy($this);
            $mock->setReturnValue("anotherMethod", 33, array(3));
            $mock->setReturnValue("anotherMethod", 22);
            $mock->setReturnValueAt(2, "anotherMethod", 44, array(3));
            $this->assertEqual($mock->anotherMethod(), 22);
            $this->assertEqual($mock->anotherMethod(3), 33);
            $this->assertEqual($mock->anotherMethod(3), 44);
        }
        
        function testReferences() {
            $mock = &new TestDummy($this);
            $object = new Dummy();
            $mock->setReturnReferenceAt(0, "anotherMethod", $object, array(3));
            $this->assertReference($mock->anotherMethod(3), $object);
        }
        
        function testExpectations() {
            $mock = &new TestDummy($this);
            $mock->expectCallCount("anotherMethod", 2);
            $mock->expectArguments("anotherMethod", array(77));
            $mock->expectArgumentsAt(1, "anotherMethod", array(66));
            $mock->anotherMethod(77);
            $mock->anotherMethod(66);
            $mock->tally();
        }
        
        function testAdditionalPartialMockCode() {
            $dummy = &new TestDummy($this);
            $this->assertEqual($dummy->sayHello(), 'Hello');
        }
        
        function testSettingExpectationOnMissingMethodThrowsError() {
            $mock = &new TestDummy($this);
            $mock->expectCallCount("aMissingMethod", 2);
            $this->assertError();
        }
    }
?>