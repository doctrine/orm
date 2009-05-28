<?php
	require_once('simpletest/autorun.php');
	require_once('simpletest/mock_objects.php');
	
	require_once(dirname(__FILE__).'/../../annotations.php');
	
	interface DummyInterface {}
	
	class ParentExample {}
	
	/** @FirstAnnotation @SecondAnnotation */
	class Example extends ParentExample implements DummyInterface {
		/** @SecondAnnotation */
		private $exampleProperty;
		
		public $publicOne;
		
		public function __construct() {}
		
		/** @FirstAnnotation */
		public function exampleMethod() {
		}
		
		private function justPrivate() {}
	}
	
	/** @FirstAnnotation(1) @FirstAnnotation(2) @SecondAnnotation(3) */
	class MultiExample {
		/** @FirstAnnotation(1) @FirstAnnotation(2) @SecondAnnotation(3) */
		public $property;
		
		/** @FirstAnnotation(1) @FirstAnnotation(2) @SecondAnnotation(3) */
		public function aMethod() {}
	}
	
	class FirstAnnotation extends Annotation {}
	class SecondAnnotation extends Annotation {}
	
	class TestOfAnnotations extends UnitTestCase {
		public function testReflectionAnnotatedClass() {
			$reflection = new ReflectionAnnotatedClass('Example');
			$this->assertTrue($reflection->hasAnnotation('FirstAnnotation'));
			$this->assertTrue($reflection->hasAnnotation('SecondAnnotation'));
			$this->assertFalse($reflection->hasAnnotation('NonExistentAnnotation'));
			$this->assertIsA($reflection->getAnnotation('FirstAnnotation'), 'FirstAnnotation');
			$this->assertIsA($reflection->getAnnotation('SecondAnnotation'), 'SecondAnnotation');
			
			$annotations = $reflection->getAnnotations();
			$this->assertEqual(count($annotations), 2);
			$this->assertIsA($annotations[0], 'FirstAnnotation');
			$this->assertIsA($annotations[1], 'SecondAnnotation');
			$this->assertFalse($reflection->getAnnotation('NonExistentAnnotation'));
			
			$this->assertIsA($reflection->getConstructor(), 'ReflectionAnnotatedMethod');
			$this->assertIsA($reflection->getMethod('exampleMethod'), 'ReflectionAnnotatedMethod');
			foreach($reflection->getMethods() as $method) {
				$this->assertIsA($method, 'ReflectionAnnotatedMethod');
			}
			
			$this->assertIsA($reflection->getProperty('exampleProperty'), 'ReflectionAnnotatedProperty');
			foreach($reflection->getProperties() as $property) {
				$this->assertIsA($property, 'ReflectionAnnotatedProperty');
			}
			
			foreach($reflection->getInterfaces() as $interface) {
				$this->assertIsA($interface, 'ReflectionAnnotatedClass');
			}
			
			$this->assertIsA($reflection->getParentClass(), 'ReflectionAnnotatedClass');
		}
		
		public function testReflectionAnnotatedMethod() {
			$reflection = new ReflectionAnnotatedMethod('Example', 'exampleMethod');
			$this->assertTrue($reflection->hasAnnotation('FirstAnnotation'));
			$this->assertFalse($reflection->hasAnnotation('NonExistentAnnotation'));
			$this->assertIsA($reflection->getAnnotation('FirstAnnotation'), 'FirstAnnotation');
			$this->assertFalse($reflection->getAnnotation('NonExistentAnnotation'));
			
			$annotations = $reflection->getAnnotations();
			$this->assertEqual(count($annotations), 1);
			$this->assertIsA($annotations[0], 'FirstAnnotation');
			
			$this->assertIsA($reflection->getDeclaringClass(), 'ReflectionAnnotatedClass');
		}
		
		public function testReflectionAnnotatedProperty() {
			$reflection = new ReflectionAnnotatedProperty('Example', 'exampleProperty');
			$this->assertTrue($reflection->hasAnnotation('SecondAnnotation'));
			$this->assertFalse($reflection->hasAnnotation('FirstAnnotation'));
			$this->assertIsA($reflection->getAnnotation('SecondAnnotation'), 'SecondAnnotation');
			$this->assertFalse($reflection->getAnnotation('NonExistentAnnotation'));
			
			$annotations = $reflection->getAnnotations();
			$this->assertEqual(count($annotations), 1);
			$this->assertIsA($annotations[0], 'SecondAnnotation');
			
			$this->assertIsA($reflection->getDeclaringClass(), 'ReflectionAnnotatedClass');
		}
		
		public function testReflectionClassCanFilterMethodsByAccess() {
			$reflection = new ReflectionAnnotatedClass('Example');
			$privateMethods = $reflection->getMethods(ReflectionMethod::IS_PRIVATE);
			$this->assertEqual(count($privateMethods), 1);
			$this->assertEqual($privateMethods[0]->getName(), 'justPrivate');
		}
		
		public function testReflectionClassCanFilterPropertiesByAccess() {
			$reflection = new ReflectionAnnotatedClass('Example');
			$privateProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
			$this->assertEqual(count($privateProperties), 1);
			$this->assertEqual($privateProperties[0]->getName(), 'publicOne');
		}
		
		public function testReflectionClassShouldReturnAllMethodsWithNoFilter() {
			$reflection = new ReflectionAnnotatedClass('Example');
			$methods = $reflection->getMethods();
			$this->assertEqual(count($methods), 3);
		}
		
		public function testReflectionClassShouldReturnAllPropertiesWithNoFilter() {
			$reflection = new ReflectionAnnotatedClass('Example');
			$properties = $reflection->getProperties();
			$this->assertEqual(count($properties), 2);
		}
		
		public function testMultipleAnnotationsOnClass() {
			$reflection = new ReflectionAnnotatedClass('MultiExample');
			$annotations = $reflection->getAllAnnotations();
			$this->assertEqual(count($annotations), 3);
			$this->assertEqual($annotations[0]->value, 1);
			$this->assertEqual($annotations[1]->value, 2);
			$this->assertEqual($annotations[2]->value, 3);
			$this->assertIsA($annotations[0], 'FirstAnnotation');
			$this->assertIsA($annotations[1], 'FirstAnnotation');
			$this->assertIsA($annotations[2], 'SecondAnnotation');
		}
		
		public function testMultipleAnnotationsOnClassWithRestriction() {
			$reflection = new ReflectionAnnotatedClass('MultiExample');
			$annotations = $reflection->getAllAnnotations('FirstAnnotation');
			$this->assertEqual(count($annotations), 2);
			$this->assertEqual($annotations[0]->value, 1);
			$this->assertEqual($annotations[1]->value, 2);
			$this->assertIsA($annotations[0], 'FirstAnnotation');
			$this->assertIsA($annotations[1], 'FirstAnnotation');
		}
		
		public function testMultipleAnnotationsOnProperty() {
			$reflection = new ReflectionAnnotatedClass('MultiExample');
			$reflection = $reflection->getProperty('property');
			$annotations = $reflection->getAllAnnotations();
			$this->assertEqual(count($annotations), 3);
			$this->assertEqual($annotations[0]->value, 1);
			$this->assertEqual($annotations[1]->value, 2);
			$this->assertEqual($annotations[2]->value, 3);
			$this->assertIsA($annotations[0], 'FirstAnnotation');
			$this->assertIsA($annotations[1], 'FirstAnnotation');
			$this->assertIsA($annotations[2], 'SecondAnnotation');
		}

		public function testMultipleAnnotationsOnPropertyWithRestriction() {
			$reflection = new ReflectionAnnotatedClass('MultiExample');
			$reflection = $reflection->getProperty('property');
			$annotations = $reflection->getAllAnnotations('FirstAnnotation');
			$this->assertEqual(count($annotations), 2);
			$this->assertEqual($annotations[0]->value, 1);
			$this->assertEqual($annotations[1]->value, 2);
			$this->assertIsA($annotations[0], 'FirstAnnotation');
			$this->assertIsA($annotations[1], 'FirstAnnotation');
		}
		
		public function testMultipleAnnotationsOnMethod() {
			$reflection = new ReflectionAnnotatedClass('MultiExample');
			$reflection = $reflection->getMethod('aMethod');
			$annotations = $reflection->getAllAnnotations();
			$this->assertEqual(count($annotations), 3);
			$this->assertEqual($annotations[0]->value, 1);
			$this->assertEqual($annotations[1]->value, 2);
			$this->assertEqual($annotations[2]->value, 3);
			$this->assertIsA($annotations[0], 'FirstAnnotation');
			$this->assertIsA($annotations[1], 'FirstAnnotation');
			$this->assertIsA($annotations[2], 'SecondAnnotation');
		}

		public function testMultipleAnnotationsOnMethodWithRestriction() {
			$reflection = new ReflectionAnnotatedClass('MultiExample');
			$reflection = $reflection->getMethod('aMethod');
			$annotations = $reflection->getAllAnnotations('FirstAnnotation');
			$this->assertEqual(count($annotations), 2);
			$this->assertEqual($annotations[0]->value, 1);
			$this->assertEqual($annotations[1]->value, 2);
			$this->assertIsA($annotations[0], 'FirstAnnotation');
			$this->assertIsA($annotations[1], 'FirstAnnotation');
		}
	}
	
	Mock::generatePartial('AnnotationsBuilder', 'MockedAnnotationsBuilder', array('getDocComment'));
	
	class TestOfPerformanceFeatures extends UnitTestCase {
		public function setUp() {
			AnnotationsBuilder::clearCache();
		}

		public function tearDown() {
			AnnotationsBuilder::clearCache();
		}
	
		public function testBuilderShouldCacheResults() {
			$builder = new MockedAnnotationsBuilder;
			$reflection = new ReflectionClass('Example');
			$builder->build($reflection);
			$builder->build($reflection);
			$builder->expectOnce('getDocComment');
		}
	}
	
	class TestOfSupportingFeatures extends UnitTestCase {
		public function setUp() {
			Addendum::resetIgnoredAnnotations();
		}
		
		public function tearDown() {
			Addendum::resetIgnoredAnnotations();
		}
	
		public function testIgnoredAnnotationsAreNotUsed() {
			Addendum::ignore('FirstAnnotation', 'SecondAnnotation');
			$reflection = new ReflectionAnnotatedClass('Example');
			$this->assertFalse($reflection->hasAnnotation('FirstAnnotation'));
			$this->assertFalse($reflection->hasAnnotation('SecondAnnotation'));
		}
	}
?>
