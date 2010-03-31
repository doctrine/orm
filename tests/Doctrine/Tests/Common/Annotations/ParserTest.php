<?php

namespace Doctrine\Tests\Common\Annotations;

use Doctrine\Common\Annotations\Parser;

require_once __DIR__ . '/../../TestInit.php';

class ParserTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testBasicAnnotations()
    {
        $parser = $this->createTestParser();
        
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

        // Numerical arrays
        $result = $parser->parse('@Name({2="foo", 4="bar"})');
        $annot = $result['Doctrine\Tests\Common\Annotations\Name'];
        $this->assertTrue(is_array($annot->value));
        $this->assertEquals('foo', $annot->value[2]);
        $this->assertEquals('bar', $annot->value[4]);
        $this->assertFalse(isset($annot->value[0]));
        $this->assertFalse(isset($annot->value[1]));
        $this->assertFalse(isset($annot->value[3]));
        
        // Nested arrays with nested annotations
        $result = $parser->parse('@Name(foo={1,2, {"key"=@Name}})');
        $annot = $result['Doctrine\Tests\Common\Annotations\Name'];

        $this->assertTrue($annot instanceof Name);
        $this->assertNull($annot->value);
        $this->assertEquals(3, count($annot->foo));
        $this->assertEquals(1, $annot->foo[0]);
        $this->assertEquals(2, $annot->foo[1]);
        $this->assertTrue(is_array($annot->foo[2]));
        
        $nestedArray = $annot->foo[2];
        $this->assertTrue(isset($nestedArray['key']));
        $this->assertTrue($nestedArray['key'] instanceof Name);
        
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
 * @Doctrine\Tests\Common\Annotations\Name(foo="bar")
 */
DOCBLOCK;

        $result = $parser->parse($docblock);
        $this->assertEquals(1, count($result));
        $annot = $result['Doctrine\Tests\Common\Annotations\Name'];
        $this->assertTrue($annot instanceof Name);
        $this->assertEquals("bar", $annot->foo);
    }

    /**
     * @group DDC-77
     */
    public function testAnnotationWithoutClassIsIgnoredWithoutWarning()
    {
        $parser = new Parser();
        $result = $parser->parse("@param");

        $this->assertEquals(0, count($result));
    }

    public function testAnnotationDontAcceptSingleQuotes()
    {
        $this->setExpectedException(
            'Doctrine\Common\Annotations\AnnotationException',
            "[Syntax Error] Expected PlainValue, got ''' at position 10."
        );

        $parser = $this->createTestParser();
        $parser->parse("@Name(foo='bar')");
    }
    
    /**
     * @group parse
     */
    public function testAnnotationNamespaceAlias()
    {
        $parser = $this->createTestParser();
        $parser->setAnnotationNamespaceAlias('Doctrine\Tests\Common\Annotations\\', 'alias');
        $docblock = <<<DOCBLOCK
/**
 * Some nifty class.
 * 
 * @author Mr.X
 * @alias:Name(foo="stuff")
 */
DOCBLOCK;
        
        $result = $parser->parse($docblock);
        $this->assertEquals(1, count($result));
        $annot = $result['Doctrine\Tests\Common\Annotations\Name'];
        $this->assertTrue($annot instanceof Name);
        $this->assertEquals("stuff", $annot->foo);
    }

    public function createTestParser()
    {
        $parser = new Parser();
        $parser->setDefaultAnnotationNamespace('Doctrine\Tests\Common\Annotations\\');
        return $parser;
    }

    /**
     * @group DDC-78
     */
    public function testSyntaxErrorWithContextDescription()
    {
        $this->setExpectedException(
            'Doctrine\Common\Annotations\AnnotationException',
            "[Syntax Error] Expected PlainValue, got ''' at position 10 ".
            "in class \Doctrine\Tests\Common\Annotations\Name"
        );

        $parser = $this->createTestParser();
        $parser->parse("@Name(foo='bar')", "class \Doctrine\Tests\Common\Annotations\Name");
    }
    
    /**
     * @group DDC-183
     */
    public function testSyntaxErrorWithUnknownCharacters()
    {
        $docblock = <<<DOCBLOCK
/**
 * @test at.
 */
class A {
}
DOCBLOCK;
        
        //$lexer = new \Doctrine\Common\Annotations\Lexer();
        //$lexer->setInput(trim($docblock, '/ *'));
        //var_dump($lexer);
        
        try {
            $parser = $this->createTestParser();
            $result = $parser->parse($docblock);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}

class Name extends \Doctrine\Common\Annotations\Annotation {
    public $foo;
}