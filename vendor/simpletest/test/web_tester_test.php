<?php
    // $Id: web_tester_test.php,v 1.10 2004/11/30 05:34:00 lastcraft Exp $
    
    class TestOfFieldExpectation extends UnitTestCase {
        
        function testStringMatchingIsCaseSensitive() {
            $expectation = new FieldExpectation('a');
            $this->assertTrue($expectation->test('a'));
            $this->assertTrue($expectation->test(array('a')));
            $this->assertFalse($expectation->test('A'));
        }
        
        function testMatchesInteger() {
            $expectation = new FieldExpectation('1');
            $this->assertTrue($expectation->test('1'));
            $this->assertTrue($expectation->test(1));
            $this->assertTrue($expectation->test(array('1')));
            $this->assertTrue($expectation->test(array(1)));
        }
        
        function testNonStringFailsExpectation() {
            $expectation = new FieldExpectation('a');
            $this->assertFalse($expectation->test(null));
        }
        
        function testUnsetFieldCanBeTestedFor() {
            $expectation = new FieldExpectation(false);
            $this->assertTrue($expectation->test(false));
        }
        
        function testMultipleValuesCanBeInAnyOrder() {
            $expectation = new FieldExpectation(array('a', 'b'));
            $this->assertTrue($expectation->test(array('a', 'b')));
            $this->assertTrue($expectation->test(array('b', 'a')));
            $this->assertFalse($expectation->test(array('a', 'a')));            
            $this->assertFalse($expectation->test('a'));            
        }
        
        function testSingleItemCanBeArrayOrString() {
            $expectation = new FieldExpectation(array('a'));
            $this->assertTrue($expectation->test(array('a')));
            $this->assertTrue($expectation->test('a'));
        }
    }
    
    class TestOfHeaderExpectations extends UnitTestCase {
        
        function testExpectingOnlyTheHeaderName() {
            $expectation = new HttpHeaderExpectation('a');
            $this->assertIdentical($expectation->test(false), false);
            $this->assertIdentical($expectation->test('a: A'), true);
            $this->assertIdentical($expectation->test('A: A'), true);
            $this->assertIdentical($expectation->test('a: B'), true);
            $this->assertIdentical($expectation->test(' a : A '), true);
        }
        
        function testHeaderValueAsWell() {
            $expectation = new HttpHeaderExpectation('a', 'A');
            $this->assertIdentical($expectation->test(false), false);
            $this->assertIdentical($expectation->test('a: A'), true);
            $this->assertIdentical($expectation->test('A: A'), true);
            $this->assertIdentical($expectation->test('A: a'), false);
            $this->assertIdentical($expectation->test('a: B'), false);
            $this->assertIdentical($expectation->test(' a : A '), true);
            $this->assertIdentical($expectation->test(' a : AB '), false);
        }
        
        function testMultilineSearch() {
            $expectation = new HttpHeaderExpectation('a', 'A');
            $this->assertIdentical($expectation->test("aa: A\r\nb: B\r\nc: C"), false);
            $this->assertIdentical($expectation->test("aa: A\r\na: A\r\nb: B"), true);
        }
        
        function testMultilineSearchWithPadding() {
            $expectation = new HttpHeaderExpectation('a', ' A ');
            $this->assertIdentical($expectation->test("aa:A\r\nb:B\r\nc:C"), false);
            $this->assertIdentical($expectation->test("aa:A\r\na:A\r\nb:B"), true);
        }
        
        function testPatternMatching() {
            $expectation = new HttpHeaderPatternExpectation('a', '/A/');
            $this->assertIdentical($expectation->test('a: A'), true);
            $this->assertIdentical($expectation->test('A: A'), true);
            $this->assertIdentical($expectation->test('A: a'), false);
            $this->assertIdentical($expectation->test('a: B'), false);
            $this->assertIdentical($expectation->test(' a : A '), true);
            $this->assertIdentical($expectation->test(' a : AB '), true);
        }
        
        function testCaseInsensitivePatternMatching() {
            $expectation = new HttpHeaderPatternExpectation('a', '/A/i');
            $this->assertIdentical($expectation->test('a: a'), true);
            $this->assertIdentical($expectation->test('a: B'), false);
            $this->assertIdentical($expectation->test(' a : A '), true);
            $this->assertIdentical($expectation->test(' a : BAB '), true);
            $this->assertIdentical($expectation->test(' a : bab '), true);
        }
        
        function testUnwantedHeader() {
            $expectation = new HttpUnwantedHeaderExpectation('a');
            $this->assertIdentical($expectation->test(''), true);
            $this->assertIdentical($expectation->test('stuff'), true);
            $this->assertIdentical($expectation->test('b: B'), true);
            $this->assertIdentical($expectation->test('a: A'), false);
            $this->assertIdentical($expectation->test('A: A'), false);
        }
        
        function testMultilineUnwantedSearch() {
            $expectation = new HttpUnwantedHeaderExpectation('a');
            $this->assertIdentical($expectation->test("aa:A\r\nb:B\r\nc:C"), true);
            $this->assertIdentical($expectation->test("aa:A\r\na:A\r\nb:B"), false);
        }
    }
    
    class TestOfTextExpectations extends UnitTestCase {
        
        function testMatchingSubString() {
            $expectation = new WantedTextExpectation('wanted');
            $this->assertIdentical($expectation->test(''), false);
            $this->assertIdentical($expectation->test('Wanted'), false);
            $this->assertIdentical($expectation->test('wanted'), true);
            $this->assertIdentical($expectation->test('the wanted text is here'), true);
        }
        
        function testNotMatchingSubString() {
            $expectation = new UnwantedTextExpectation('wanted');
            $this->assertIdentical($expectation->test(''), true);
            $this->assertIdentical($expectation->test('Wanted'), true);
            $this->assertIdentical($expectation->test('wanted'), false);
            $this->assertIdentical($expectation->test('the wanted text is here'), false);
        }
    }
?>