<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\Lexer;

class LexerTest extends \Doctrine\Tests\OrmTestCase
{
    //private $_lexer;

    protected function setUp() {
    }

    /**
     * @dataProvider provideTokens
     */
    public function testScannerRecognizesTokens($type, $value)
    {
        $lexer = new Lexer($value);

        $lexer->moveNext();
        $token = $lexer->lookahead;

        $this->assertEquals($type, $token['type']);
        $this->assertEquals($value, $token['value']);
    }

    public function testScannerRecognizesTerminalString()
    {
        /*
         * "all" looks like an identifier, but in fact it's a reserved word
         * (a terminal string). It's up to the parser to accept it as an identifier
         * (with its literal value) when appropriate.
         */

        $lexer = new Lexer('all');

        $lexer->moveNext();
        $token = $lexer->lookahead;

        $this->assertEquals(Lexer::T_ALL, $token['type']);
    }

    public function testScannerRecognizesDecimalInteger()
    {
        $lexer = new Lexer('1234');
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_INTEGER, $token['type']);
        $this->assertEquals(1234, $token['value']);
    }

    public function testScannerRecognizesFloat()
    {
        $lexer = new Lexer('1.234');
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_FLOAT, $token['type']);
        $this->assertEquals(1.234, $token['value']);
    }

    public function testScannerRecognizesFloatWithExponent()
    {
        $lexer = new Lexer('1.2e3');
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_FLOAT, $token['type']);
        $this->assertEquals(1.2e3, $token['value']);
    }

    public function testScannerRecognizesFloatWithExponent2()
    {
        $lexer = new Lexer('0.2e3');
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_FLOAT, $token['type']);
        $this->assertEquals(.2e3, $token['value']);
    }

    public function testScannerRecognizesFloatWithNegativeExponent()
    {
        $lexer = new Lexer('7E-10');
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_FLOAT, $token['type']);
        $this->assertEquals(7E-10, $token['value']);
    }

    public function testScannerRecognizesFloatBig()
    {
        $lexer = new Lexer('123456789.01');
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_FLOAT, $token['type']);
        $this->assertEquals(1.2345678901e8, $token['value']);
    }

    public function testScannerRecognizesFloatContainingWhitespace()
    {
        $lexer = new Lexer('-   1.234e2');
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_MINUS, $token['type']);
        $this->assertEquals('-', $token['value']);

        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_FLOAT, $token['type']);
        $this->assertNotEquals(-1.234e2, $token['value']);
        $this->assertEquals(1.234e2, $token['value']);
    }

    public function testScannerRecognizesStringContainingWhitespace()
    {
        $lexer = new Lexer("'This is a string.'");
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_STRING, $token['type']);
        $this->assertEquals("This is a string.", $token['value']);
    }

    public function testScannerRecognizesStringContainingSingleQuotes()
    {
        $lexer = new Lexer("'abc''defg'''");
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_STRING, $token['type']);
        $this->assertEquals("abc'defg'", $token['value']);
    }

    public function testScannerRecognizesInputParameter()
    {
        $lexer = new Lexer('?1');
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_INPUT_PARAMETER, $token['type']);
        $this->assertEquals('?1', $token['value']);
    }

    public function testScannerRecognizesNamedInputParameter()
    {
        $lexer = new Lexer(':name');
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_INPUT_PARAMETER, $token['type']);
        $this->assertEquals(':name', $token['value']);
    }

    public function testScannerRecognizesNamedInputParameterStartingWithUnderscore()
    {
        $lexer = new Lexer(':_name');
        $lexer->moveNext();
        $token = $lexer->lookahead;
        $this->assertEquals(Lexer::T_INPUT_PARAMETER, $token['type']);
        $this->assertEquals(':_name', $token['value']);
    }

    public function testScannerTokenizesASimpleQueryCorrectly()
    {
        $dql = "SELECT u FROM My\Namespace\User u WHERE u.name = 'Jack O''Neil'";
        $lexer = new Lexer($dql);

        $tokens = array(
            array(
                'value' => 'SELECT',
                'type'  => Lexer::T_SELECT,
                'position' => 0
            ),
            array(
                'value' => 'u',
                'type'  => Lexer::T_IDENTIFIER,
                'position' => 7
            ),
            array(
                'value' => 'FROM',
                'type'  => Lexer::T_FROM,
                'position' => 9
            ),
            array(
                'value' => 'My\Namespace\User',
                'type'  => Lexer::T_FULLY_QUALIFIED_NAME,
                'position' => 14
            ),
            array(
                'value' => 'u',
                'type'  => Lexer::T_IDENTIFIER,
                'position' => 32
            ),
            array(
                'value' => 'WHERE',
                'type'  => Lexer::T_WHERE,
                'position' => 34
            ),
            array(
                'value' => 'u',
                'type'  => Lexer::T_IDENTIFIER,
                'position' => 40
            ),
            array(
                'value' => '.',
                'type'  => Lexer::T_DOT,
                'position' => 41
            ),
            array(
                'value' => 'name',
                'type'  => Lexer::T_IDENTIFIER,
                'position' => 42
            ),
            array(
                'value' => '=',
                'type'  => Lexer::T_EQUALS,
                'position' => 47
            ),
            array(
                'value' => "Jack O'Neil",
                'type'  => Lexer::T_STRING,
                'position' => 49
            )
        );

        foreach ($tokens as $expected) {
            $lexer->moveNext();
            $actual = $lexer->lookahead;
            $this->assertEquals($expected['value'], $actual['value']);
            $this->assertEquals($expected['type'], $actual['type']);
            $this->assertEquals($expected['position'], $actual['position']);
        }

        $this->assertFalse($lexer->moveNext());
    }

    public function provideTokens()
    {
        return array(
            array(Lexer::T_IDENTIFIER, 'u'), // one char
            array(Lexer::T_IDENTIFIER, 'someIdentifier'),
            array(Lexer::T_IDENTIFIER, 's0m31d3nt1f13r'), // including digits
            array(Lexer::T_IDENTIFIER, 'some_identifier'), // including underscore
            array(Lexer::T_IDENTIFIER, '_some_identifier'), // starts with underscore
            array(Lexer::T_IDENTIFIER, 'comma'), // name of a token class with value < 100 (whitebox test)
            array(Lexer::T_FULLY_QUALIFIED_NAME, 'Some\Class'), // DQL class reference
            array(Lexer::T_ALIASED_NAME, 'Some:Name'),
            array(Lexer::T_ALIASED_NAME, 'Some:Subclassed\Name')
        );
    }
}
