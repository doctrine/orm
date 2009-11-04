<?php

namespace Doctrine\Tests\Common\Annotations;

use Doctrine\Common\Annotations\AnnotationReader;

require_once __DIR__ . '/../../TestInit.php';

class AnnotationReaderTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testAnnotations()
    {
        $reader = new AnnotationReader(new \Doctrine\Common\Cache\ArrayCache);
        $reader->setDefaultAnnotationNamespace('Doctrine\Tests\Common\Annotations\\');
    
        $class = new \ReflectionClass('Doctrine\Tests\Common\Annotations\DummyClass');
        $classAnnots = $reader->getClassAnnotations($class);
        
        $annotName = 'Doctrine\Tests\Common\Annotations\DummyAnnotation';
        $this->assertEquals(1, count($classAnnots));
        $this->assertTrue($classAnnots[$annotName] instanceof DummyAnnotation);
        $this->assertEquals("hello", $classAnnots[$annotName]->dummyValue);
        
        $field1Prop = $class->getProperty('field1');
        $propAnnots = $reader->getPropertyAnnotations($field1Prop);
        $this->assertEquals(1, count($propAnnots));
        $this->assertTrue($propAnnots[$annotName] instanceof DummyAnnotation);
        $this->assertEquals("fieldHello", $propAnnots[$annotName]->dummyValue);
        
        $getField1Method = $class->getMethod('getField1');
        $methodAnnots = $reader->getMethodAnnotations($getField1Method);
        $this->assertEquals(1, count($methodAnnots));
        $this->assertTrue($methodAnnots[$annotName] instanceof DummyAnnotation);
        $this->assertEquals(array(1, 2, "three"), $methodAnnots[$annotName]->value);
        
        $field2Prop = $class->getProperty('field2');
        $propAnnots = $reader->getPropertyAnnotations($field2Prop);
        $this->assertEquals(1, count($propAnnots));
        $this->assertTrue(isset($propAnnots['Doctrine\Tests\Common\Annotations\DummyJoinTable']));
        $joinTableAnnot = $propAnnots['Doctrine\Tests\Common\Annotations\DummyJoinTable'];
        $this->assertEquals(1, count($joinTableAnnot->joinColumns));
        $this->assertEquals(1, count($joinTableAnnot->inverseJoinColumns));
        $this->assertTrue($joinTableAnnot->joinColumns[0] instanceof DummyJoinColumn);
        $this->assertTrue($joinTableAnnot->inverseJoinColumns[0] instanceof DummyJoinColumn);
        $this->assertEquals('col1', $joinTableAnnot->joinColumns[0]->name);
        $this->assertEquals('col2', $joinTableAnnot->joinColumns[0]->referencedColumnName);
        $this->assertEquals('col3', $joinTableAnnot->inverseJoinColumns[0]->name);
        $this->assertEquals('col4', $joinTableAnnot->inverseJoinColumns[0]->referencedColumnName);

        $dummyAnnot = $reader->getMethodAnnotation($class->getMethod('getField1'), 'Doctrine\Tests\Common\Annotations\DummyAnnotation');
        $this->assertEquals('', $dummyAnnot->dummyValue);
        $this->assertEquals(array(1, 2, 'three'), $dummyAnnot->value);

        $dummyAnnot = $reader->getPropertyAnnotation($class->getProperty('field1'), 'Doctrine\Tests\Common\Annotations\DummyAnnotation');
        $this->assertEquals('fieldHello', $dummyAnnot->dummyValue);

        $classAnnot = $reader->getClassAnnotation($class, 'Doctrine\Tests\Common\Annotations\DummyAnnotation');
        $this->assertEquals('hello', $classAnnot->dummyValue);
    }

    public function testClassSyntaxErrorContext()
    {
        $this->setExpectedException(
            "Doctrine\Common\Annotations\AnnotationException",
            "[Syntax Error] Expected Doctrine\Common\Annotations\Lexer::T_IDENTIFIER, got ')' at position 18 in class ".
            "Doctrine\Tests\Common\Annotations\DummyClassSyntaxError."
        );

        $class = new \ReflectionClass('\Doctrine\Tests\Common\Annotations\DummyClassSyntaxError');

        $reader = $this->createAnnotationReader();
        $reader->getClassAnnotations($class);
    }

    public function testMethodSyntaxErrorContext()
    {
        $this->setExpectedException(
            "Doctrine\Common\Annotations\AnnotationException",
            "[Syntax Error] Expected Doctrine\Common\Annotations\Lexer::T_IDENTIFIER, got ')' at position 18 in ".
            "method Doctrine\Tests\Common\Annotations\DummyClassMethodSyntaxError::foo()."
        );

        $class = new \ReflectionClass('\Doctrine\Tests\Common\Annotations\DummyClassMethodSyntaxError');
        $method = $class->getMethod('foo');

        $reader = $this->createAnnotationReader();
        $reader->getMethodAnnotations($method);
    }

    public function testPropertySyntaxErrorContext()
    {
        $this->setExpectedException(
            "Doctrine\Common\Annotations\AnnotationException",
            "[Syntax Error] Expected Doctrine\Common\Annotations\Lexer::T_IDENTIFIER, got ')' at position 18 in ".
            "property Doctrine\Tests\Common\Annotations\DummyClassPropertySyntaxError::\$foo."
        );

        $class = new \ReflectionClass('\Doctrine\Tests\Common\Annotations\DummyClassPropertySyntaxError');
        $property = $class->getProperty('foo');

        $reader = $this->createAnnotationReader();
        $reader->getPropertyAnnotations($property);
    }

    public function createAnnotationReader()
    {
        $reader = new AnnotationReader(new \Doctrine\Common\Cache\ArrayCache);
        $reader->setDefaultAnnotationNamespace('Doctrine\Tests\Common\Annotations\\');
        return $reader;
    }
}

/**
 * A description of this class.
 * 
 * @author robo
 * @since 2.0
 * @DummyAnnotation(dummyValue="hello")
 */
class DummyClass {
    /**
     * A nice property.
     * 
     * @var mixed
     * @DummyAnnotation(dummyValue="fieldHello")
     */
    private $field1;
    
    /**
     * @DummyJoinTable(name="join_table",
     *      joinColumns={
     *          @DummyJoinColumn(name="col1", referencedColumnName="col2")
     *      },
     *      inverseJoinColumns={
     *          @DummyJoinColumn(name="col3", referencedColumnName="col4")
     *      })
     */
    private $field2;
    
    /**
     * Gets the value of field1.
     *
     * @return mixed
     * @DummyAnnotation({1,2,"three"})
     */
    public function getField1() {
    }
}

class DummyAnnotation extends \Doctrine\Common\Annotations\Annotation {
    public $dummyValue;
}
class DummyJoinColumn extends \Doctrine\Common\Annotations\Annotation {
    public $name;
    public $referencedColumnName;
}
class DummyJoinTable extends \Doctrine\Common\Annotations\Annotation {
    public $name;
    public $joinColumns;
    public $inverseJoinColumns;
}

/**
 * @DummyAnnotation(@)
 */
class DummyClassSyntaxError
{
    
}

class DummyClassMethodSyntaxError
{
    /**
     * @DummyAnnotation(@)
     */
    public function foo()
    {
        
    }
}

class DummyClassPropertySyntaxError
{
    /**
     * @DummyAnnotation(@)
     */
    public $foo;
}