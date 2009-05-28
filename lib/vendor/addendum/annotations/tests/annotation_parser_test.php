<?php
	require_once('simpletest/autorun.php');
	require_once(dirname(__FILE__).'/../annotation_parser.php');

	class TestOfMatchers extends UnitTestCase {
		public function testRegexMatcherShouldMatchPatternAndReturnLengthOfMatch() {
			$matcher = new RegexMatcher('[0-9]+');
			$this->assertIdentical($matcher->matches('1234a', $value), 4);
			$this->assertIdentical($value, '1234');
		}
		
		public function testRegexMatcherShouldReturnFalseOnNoMatch() {
			$matcher = new RegexMatcher('[0-9]+');
			$this->assertFalse($matcher->matches('abc123', $value));
		}
		
		public function testParallelMatcherShouldMatchLongerStringOnColision() {
			$matcher = new ParallelMatcher;
			$matcher->add(new RegexMatcher('true'));
			$matcher->add(new RegexMatcher('.+'));
			$this->assertEqual($matcher->matches('truestring', $value), 10);
			$this->assertEqual($value, 'truestring');
		}
		
		public function testSerialMatcherShouldMatchAllParts() {
			$matcher = new SerialMatcher;
			$matcher->add(new RegexMatcher('[a-zA-Z0-9_]+'));
			$matcher->add(new RegexMatcher('='));
			$matcher->add(new RegexMatcher('[0-9]+'));
			$this->assertEqual($matcher->matches('key=20', $value), 6);
			$this->assertEqual($value, 'key=20');
		}
		
		public function testSerialMatcherShouldFailIfAnyPartDoesNotMatch() {
			$matcher = new SerialMatcher;
			$matcher->add(new RegexMatcher('[a-zA-Z0-9_]+'));
			$matcher->add(new RegexMatcher('='));
			$matcher->add(new RegexMatcher('[0-9]+'));
			$this->assertFalse($matcher->matches('key=a20', $value));
		}
		
		public function testSimpleSerialMatcherShouldReturnRequestedPartOnMatch() {
			$matcher = new SimpleSerialMatcher(1);
			$matcher->add(new RegexMatcher('\('));
			$matcher->add(new RegexMatcher('[0-9]+'));
			$matcher->add(new RegexMatcher('\)'));
			$this->assertEqual($matcher->matches('(1234)', $value), 6);
			$this->assertEqual($value, '1234');
		}
	}
	
	class TestOfAnnotationMatchers extends UnitTestCase {
		public function testAnnotationsMatcherShouldMatchAnnotationWithGarbage() {
			$expected = array('Annotation' => array(
				array('value' => true),
			));
			$matcher = new AnnotationsMatcher;
			$this->assertMatcherResult($matcher, '/** asd bla bla @Annotation(true) */@', $expected);
		}
		
		public function testAnnotationsMatcherShouldNotMatchEmail() {
			$matcher = new AnnotationsMatcher;
			$this->assertMatcherResult($matcher, 'johno@example.com', array());
		}
		
		public function testAnnotationsMatcherShouldMatchMultipleAnnotations() {
			$expected = array('Annotation' => array(
				array('value' => true),
				array('value' => false)
			));
			$matcher = new AnnotationsMatcher;
			$this->assertMatcherResult($matcher, ' ss @Annotation(true) @Annotation(false)', $expected);
		}
		
		public function testAnnotationsMatcherShouldMatchMultipleAnnotationsOnManyLines() {
			$expected = array('Annotation' => array(
				array('value' => true),
				array('value' => false)
			));
			$block = "/** 
				@Annotation(true) 
				@Annotation(false)
			**/";
			$matcher = new AnnotationsMatcher;
			$this->assertMatcherResult($matcher, $block, $expected);
		}
		
		public function testAnnotationMatcherShouldMatchMultilineAnnotations() {
			$block= '/**
 				* @Annotation(
		   			paramOne="value1",
		   			paramTwo={
		 			"value2" ,
						{"one", "two"}
		   			},
		   			paramThree="three"
				)
 			*/';
			$expected = array('Annotation' => array(
				array(
					'paramOne' => 'value1',
					'paramTwo' => array('value2', array('one', 'two')),
					'paramThree' => 'three',
				)
			));
 			$matcher = new AnnotationsMatcher;
			$this->assertMatcherResult($matcher, $block, $expected);
		}
	
		public function testAnnotationMatcherShouldMatchSimpleAnnotation() {
			$matcher = new AnnotationMatcher;
			$this->assertNotFalse($matcher->matches('@Namespace_Annotation', $value));
			$this->assertEqual($value, array('Namespace_Annotation', array()));
		}
		
		public function testAnnotationMatcherShouldNotMatchAnnotationWithSmallStartingLetter() {
			$matcher = new AnnotationMatcher;
			$this->assertFalse($matcher->matches('@annotation', $value));
		}
		
		public function testAnnotationMatcherShouldMatchAlsoBrackets() {
			$matcher = new AnnotationMatcher;
			$this->assertEqual($matcher->matches('@Annotation()', $value), 13);
			$this->assertEqual($value, array('Annotation', array()));
		}
		
		public function testAnnotationMatcherShouldMatchValuedAnnotation() {
			$matcher = new AnnotationMatcher;
			$this->assertMatcherResult($matcher, '@Annotation(true)', array('Annotation', array('value' => true)));
		}
		
		public function testAnnotationMatcherShouldMatchMultiValuedAnnotation() {
			$matcher = new AnnotationMatcher;
			$this->assertMatcherResult($matcher, '@Annotation(key=true, key2=3.14)', array('Annotation', array('key' => true, 'key2' => 3.14)));
		}
		
		public function testParametersMatcherShouldMatchEmptyStringAndReturnEmptyArray() {
			$matcher = new AnnotationParametersMatcher;
			$this->assertIdentical($matcher->matches('', $value), 0);
			$this->assertEqual($value, array());
		}
		
		public function testParametersMatcherShouldMatchEmptyBracketsAndReturnEmptyArray() {
			$matcher = new AnnotationParametersMatcher;
			$this->assertIdentical($matcher->matches('()', $value), 2);
			$this->assertEqual($value, array());
		}
		
		public function testParametersMatcherShouldMatchMultilinedParameters() {
			$matcher = new AnnotationParametersMatcher;
			$block = "(
				key = true,
				key2 = false
			)";
			$this->assertMatcherResult($matcher, $block, array('key' => true, 'key2' => false));
		}
		
		public function testValuesMatcherShouldMatchSimpleValueOrHash() {
			$matcher = new AnnotationValuesMatcher;
			$this->assertNotFalse($matcher->matches('true', $value));
			$this->assertNotFalse($matcher->matches('key=true', $value));
		}
		
		public function testValueMatcherShouldMatchConstants() {
			$matcher = new AnnotationValueMatcher;
			$this->assertMatcherResult($matcher, 'true', true);
			$this->assertMatcherResult($matcher, 'false', false);
			$this->assertMatcherResult($matcher, 'TRUE', true);
			$this->assertMatcherResult($matcher, 'FALSE', false);
		}
		
		public function testValueMatcherShouldMatchStrings() {
			$matcher = new AnnotationValueMatcher;
			$this->assertMatcherResult($matcher, '"string"', 'string');
			$this->assertMatcherResult($matcher, "'string'", 'string');
		}
		
		public function testValueMatcherShouldMatchNumbers() {
			$matcher = new AnnotationValueMatcher;
			$this->assertMatcherResult($matcher, '-3.14', -3.14);
			$this->assertMatcherResult($matcher, '100', 100);
		}
		
		public function testValueMatcherShouldMatchArray() {
			$matcher = new AnnotationValueMatcher;
			$this->assertMatcherResult($matcher, '{1}', array(1));
		}
		
		public function testArrayMatcherShouldMatchEmptyArray() {
			$matcher = new AnnotationArrayMatcher;
			$this->assertMatcherResult($matcher, '{}', array());
		}
		
		public function testValueInArrayMatcherReturnsAValueInArray() {
			$matcher = new AnnotationValueInArrayMatcher;
			$this->assertMatcherResult($matcher, '1', array(1));
		}
		
		public function testArrayMatcherShouldMatchSimpleValueInArray() {
			$matcher = new AnnotationArrayMatcher;
			$this->assertMatcherResult($matcher, '{1}', array(1));
		}
		
		public function testArrayMatcherShouldMatchSimplePair() {
			$matcher = new AnnotationArrayMatcher;
			$this->assertMatcherResult($matcher, '{key=5}', array('key' => 5));
		}
		
		public function TODO_testArrayMatcherShouldMatchPairWithNumericKeys() {
			$matcher = new AnnotationArrayMatcher;
			$this->assertMatcherResult($matcher, '{1="one", 2="two"}', array(1 => 'one', 2 => 'two'));
		}
		
		public function testArrayMatcherShouldMatchMultiplePairs() {
			$matcher = new AnnotationArrayMatcher;
			$this->assertMatcherResult($matcher, '{key=5, "bla"=false}', array('key' => 5, 'bla' => false));
		}
		
		public function testArrayMatcherShouldMatchValuesMixedWithPairs() {
			$matcher = new AnnotationArrayMatcher;
			$this->assertMatcherResult($matcher, '{key=5, 1, 2, key2="ff"}', array('key' => 5, 1, 2, 'key2' => "ff"));
		}
		
		public function testArrayMatcherShouldMatchMoreValuesInArrayWithWhiteSpace() {
			$matcher = new AnnotationArrayMatcher;
			$this->assertMatcherResult($matcher, "{1 , 2}", array(1, 2));
		}
		
		public function testArrayMatcherShouldMatchNestedArray() {
			$matcher = new AnnotationArrayMatcher;
			$this->assertMatcherResult($matcher, "{1 , {2, 3}, 4}", array(1, array(2, 3), 4));
		}
		
		public function testArrayMatcherShouldMatchWithMoreWhiteSpace() {
			$matcher = new AnnotationArrayMatcher;
			$this->assertMatcherResult($matcher, "{ 1 , 2 , 3 }", array(1, 2, 3));
		}
		
		public function testArrayMatcherShouldMatchWithMultilineWhiteSpace() {
			$matcher = new AnnotationArrayMatcher;
			$this->assertMatcherResult($matcher, "\n{1, 2, 3\n}", array(1, 2, 3));
		}
		
		public function testNumberMatcherShouldMatchInteger() {
			$matcher = new AnnotationNumberMatcher;
			$this->assertMatcherResult($matcher, '-314', -314);
		}
		
		public function testNumberMatcherShouldMatchFloat() {
			$matcher = new AnnotationNumberMatcher;
			$this->assertMatcherResult($matcher, '-3.14', -3.14);
		}
		
		public function testHashMatcherShouldMatchSimpleHash() {
			$matcher = new AnnotationHashMatcher;
			$this->assertMatcherResult($matcher, 'key=true', array('key' => true));
		}
		
		public function testHashMatcherShouldMatchAlsoMultipleKeys() {
			$matcher = new AnnotationHashMatcher;
			$this->assertMatcherResult($matcher, 'key=true,key2=false', array('key' => true, 'key2' => false));
		}
		
		public function testHashMatcherShouldMatchAlsoMultipleKeysWithWhiteSpace() {
			$matcher = new AnnotationHashMatcher;
			$this->assertMatcherResult($matcher, "key=true\n\t\r ,\n\t\r key2=false", array('key' => true, 'key2' => false));
		}
		
		public function testPairMatcherShouldMatchNumericKey() {
			$matcher = new AnnotationPairMatcher;
			$this->assertMatcherResult($matcher, '2 = true', array(2 => true));
		}
		
		public function testPairMatcherShouldMatchAlsoWhitespace() {
			$matcher = new AnnotationPairMatcher;
			$this->assertMatcherResult($matcher, 'key = true', array('key' => true));
		}
		
		public function testKeyMatcherShouldMatchSimpleKeysOrStrings() {
			$matcher = new AnnotationKeyMatcher;
			$this->assertNotFalse($matcher->matches('key', $value));
			$this->assertNotFalse($matcher->matches('"key"', $value));
			$this->assertNotFalse($matcher->matches("'key'", $value));
		}
		
		public function testKeyMatcherShouldMatchIntegerKeys() {
			$matcher = new AnnotationKeyMatcher;
			$this->assertMatcherResult($matcher, '123', 123);
		}
		
		public function testStringMatcherShouldMatchDoubleAndSingleQuotedStringsAndHandleEscapes() {
			$matcher = new AnnotationStringMatcher;
			$this->assertMatcherResult($matcher, '"string string"', 'string string');
			$this->assertMatcherResult($matcher, "'string string'", "string string");
		}
		
		public function TODO_testStringMatcherShouldMatchEscapedStringsCorrectly() {
			$matcher = new AnnotationStringMatcher;
			$this->assertMatcherResult($matcher, '"string\"string"', 'string"string');
			$this->assertMatcherResult($matcher, "'string\'string'", "string'string");
		}
		
		private function assertNotFalse($value) {
			$this->assertNotIdentical($value, false);
		}
		
		private function assertMatcherResult($matcher, $string, $expected) {
			$this->assertNotIdentical($matcher->matches($string, $value), false);
			$this->assertIdentical($value, $expected);
		}
	}
?>