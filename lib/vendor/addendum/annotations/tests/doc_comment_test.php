<?php
	require_once('simpletest/autorun.php');
	require_once('simpletest/mock_objects.php');
	
	require_once(dirname(__FILE__).'/../doc_comment.php');
	
	Mock::generatePartial('DocComment', 'MockDocComment', array('parse'));
	
	/** class doccomment */
	class SomeClass {
		/** field doccomment */
		private $field1;
		
		private $field2;
		
		/** method1 doccomment */
		public function method1() {
		
		}
		
		public function method2() {}
		/** bad one */
	}
	
	class SomeOtherClass {
		/** field doccomment */
		private $field1;
	}
	
	class TestOfDocComment extends UnitTestCase {
		public function setUp() {
			DocComment::clearCache();
		}
	
		public function testFinderFindsClassDocBlock() {
			$reflection = new ReflectionClass('SomeClass');
			$finder = new DocComment();
			$this->assertEqual($finder->get($reflection), '/** class doccomment */');
		}
		
		public function testFinderFindsFieldDocBlock() {
			$reflection = new ReflectionClass('SomeClass');
			$property = $reflection->getProperty('field1');
			$finder = new DocComment();
			$this->assertEqual($finder->get($property), '/** field doccomment */');
			$property = $reflection->getProperty('field2');
			$finder = new DocComment();
			$this->assertFalse($finder->get($property));
		}
		
		public function testFinderFindsMethodDocBlock() {
			$reflection = new ReflectionClass('SomeClass');
			$method = $reflection->getMethod('method1');
			$finder = new DocComment();
			$this->assertEqual($finder->get($method), '/** method1 doccomment */');
			$method = $reflection->getMethod('method2');
			$finder = new DocComment();
			$this->assertFalse($finder->get($method));
		}
		
		public function testMisplacedDocCommentDoesNotCausesDisaster() {
			$reflection = new ReflectionClass('SomeOtherClass');
			$finder = new DocComment();
			$this->assertEqual($finder->get($reflection), false);
		}
		
		public function testUnanotatedClassCanHaveAnotatedField() {
			$reflection = new ReflectionClass('SomeOtherClass');
			$property = $reflection->getProperty('field1');
			$finder = new DocComment();
			$this->assertEqual($finder->get($property), '/** field doccomment */');
		}
		
		public function testParserIsOnlyCalledOncePerFile() {
			$reflection = new ReflectionClass('SomeClass');
			$finder = new MockDocComment();
			$finder->expectOnce('parse');
			$this->assertEqual($finder->get($reflection), false);
			$this->assertEqual($finder->get($reflection), false);
			
			$reflection = new ReflectionClass('SomeClass');
			$finder = new MockDocComment();
			$finder->expectNever('parse');
			$this->assertEqual($finder->get($reflection), false);
		}
	}
?>
