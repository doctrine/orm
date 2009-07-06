<?php

namespace Doctrine\Tests\Common\Annotations;

use Doctrine\Common\Annotations\Parser;

require_once __DIR__ . '/../../TestInit.php';

class ParserTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testBasicAnnotations()
    {
        $parser = new Parser;
        $parser->setDefaultAnnotationNamespace('Doctrine\Tests\Common\Annotations\\');
        
        // Marker annotation
        $result = $parser->parse("@Name");
        $annot = $result['Doctrine\Tests\Common\Annotations\Name'];
        $this->assertTrue($annot instanceof Name);
        $this->assertNull($annot->value);
        $this->assertNull($annot->foo);
        
        // Associative arrays
        $result = $parser->parse('@Name(foo={"key1" = "value1"})');
        $annot = $result['Doctrine\Tests\Common\Annotations\Name'];
        $this->assertNull($annot->value);
        $this->assertTrue(is_array($annot->foo));
        $this->assertTrue(isset($annot->foo['key1']));
        
        // Nested arrays with nested annotations
        $result = $parser->parse('@Name(foo=1, 2, 3, {1,2, {"key"=@Name}})');
        $annot = $result['Doctrine\Tests\Common\Annotations\Name'];
                
        $this->assertTrue($annot instanceof Name);
        $this->assertEquals(3, count($annot->value));
        $this->assertEquals(1, $annot->foo);
        $this->assertEquals(2, $annot->value[0]);
        $this->assertEquals(3, $annot->value[1]);
        $this->assertTrue(is_array($annot->value[2]));
        
        $nestedArray = $annot->value[2];
        $this->assertEquals(3, count($nestedArray));
        $this->assertEquals(1, $nestedArray[0]);
        $this->assertEquals(2, $nestedArray[1]);
        $this->assertTrue(is_array($nestedArray[2]));
        
        $nestedArray2 = $nestedArray[2];
        $this->assertTrue(isset($nestedArray2['key']));
        $this->assertTrue($nestedArray2['key'] instanceof Name);
        
        // Complete docblock
        $docblock = <<<DOCBLOCK
/**
 * Some nifty class.
 * 
 * @author Mr.X
 * @Name(foo="bar")
 */
DOCBLOCK;

        $result = $parser->parse($docblock);
        $this->assertEquals(1, count($result));
        $annot = $result['Doctrine\Tests\Common\Annotations\Name'];
        $this->assertTrue($annot instanceof Name);
        $this->assertEquals("bar", $annot->foo);
        $this->assertNull($annot->value);
    }
    
    public function testNamespacedAnnotations()
    {
        $parser = new Parser;
        
        $docblock = <<<DOCBLOCK
/**
 * Some nifty class.
 * 
 * @author Mr.X
 * @Doctrine.Tests.Common.Annotations.Name(foo="bar")
 */
DOCBLOCK;

         $result = $parser->parse($docblock);
         $this->assertEquals(1, count($result));
         $annot = $result['Doctrine\Tests\Common\Annotations\Name'];
         $this->assertTrue($annot instanceof Name);
         $this->assertEquals("bar", $annot->foo);
    }
}

class Name extends \Doctrine\Common\Annotations\Annotation {
    public $foo;
}