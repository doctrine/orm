<?php
	require_once('simpletest/autorun.php');
	require_once(dirname(__FILE__).'/../../annotations.php');
	
	class TestingAnnotation extends Annotation {
		public $optional = 'default';
		public $required;
	}
	
	class TestOfAnnotation extends UnitTestCase {
		public function testConstructorsFillsParameters() {
			$annotation = new TestingAnnotation(array('optional' => 1, 'required' => 2), $this);
			$this->assertEqual($annotation->optional, 1);
			$this->assertEqual($annotation->required, 2);
		}
		
		public function testConstructorThrowsErrorOnInvalidParameter() {
			$this->expectError("Property 'unknown' not defined for annotation 'TestingAnnotation'");
			$annotation = new TestingAnnotation(array('unknown' => 1), $this);
		}
		
		public function TODO_testConstructorThrowsErrorWithoutSpecifingRequiredParameters() {
			$this->expectError("Property 'required' in annotation 'TestingAnnotation' is required");
			$annotation = new TestingAnnotation();
		}
	}
?>
