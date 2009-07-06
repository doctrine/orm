<?php

namespace Doctrine\Tests\Common\Annotations;

use Doctrine\Common\Annotations\Lexer;

require_once __DIR__ . '/../../TestInit.php';

class LexerTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testMarkerAnnotation()
    {
        $lexer = new Lexer;
        
        $lexer->setInput("@Name");
        $this->assertNull($lexer->token);
        $this->assertNull($lexer->lookahead);
        
        $this->assertTrue($lexer->moveNext());
        $this->assertNull($lexer->token);
        $this->assertEquals('@', $lexer->lookahead['value']);
        
        $this->assertTrue($lexer->moveNext());
        $this->assertEquals('@', $lexer->token['value']);
        $this->assertEquals('Name', $lexer->lookahead['value']);
        
        $this->assertFalse($lexer->moveNext());
    }
}