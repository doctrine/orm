<?php
require_once 'PHPUnit/Framework.php';
require_once '../Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));

class ScannerTest extends PHPUnit_Framework_TestCase
{
    public function testScannerRecognizesIdentifierWithLengthOfOneCharacter()
    {
        $scanner = new Doctrine_Query_Scanner('u');

        $token = $scanner->scan();
        $this->assertEquals(Doctrine_Query_Token::T_IDENTIFIER, $token->getType());
        $this->assertEquals('u', $token->getValue());
    }

    public function testScannerRecognizesIdentifierConsistingOfLetters()
    {
        $scanner = new Doctrine_Query_Scanner('someIdentifier');

        $token = $scanner->scan();
        $this->assertEquals(Doctrine_Query_Token::T_IDENTIFIER, $token->getType());
        $this->assertEquals('someIdentifier', $token->getValue());
    }

    public function testScannerRecognizesIdentifierIncludingDigits()
    {
        $scanner = new Doctrine_Query_Scanner('s0m31d3nt1f13r');

        $token = $scanner->scan();
        $this->assertEquals(Doctrine_Query_Token::T_IDENTIFIER, $token->getType());
        $this->assertEquals('s0m31d3nt1f13r', $token->getValue());
    }

    public function testScannerRecognizesIdentifierIncludingUnderscore()
    {
        $scanner = new Doctrine_Query_Scanner('some_identifier');

        $token = $scanner->scan();
        $this->assertEquals(Doctrine_Query_Token::T_IDENTIFIER, $token->getType());
        $this->assertEquals('some_identifier', $token->getValue());
    }

    public function testScannerRecognizesDecimalInteger()
    {
        $scanner = new Doctrine_Query_Scanner('1234');

        $token = $scanner->scan();
        $this->assertEquals(Doctrine_Query_Token::T_NUMERIC, $token->getType());
        $this->assertEquals(1234, $token->getValue());
    }

    public function testScannerRecognizesNegativeDecimalInteger()
    {
        $scanner = new Doctrine_Query_Scanner('-123');

        $token = $scanner->scan();
        $this->assertEquals(Doctrine_Query_Token::T_NUMERIC, $token->getType());
        $this->assertEquals(-123, $token->getValue());
    }

    public function testScannerRecognizesFloat()
    {
        $scanner = new Doctrine_Query_Scanner('1.234');

        $token = $scanner->scan();
        $this->assertEquals(Doctrine_Query_Token::T_NUMERIC, $token->getType());
        $this->assertEquals(1.234, $token->getValue());
    }

    public function testScannerRecognizesFloatWithExponent()
    {
        $scanner = new Doctrine_Query_Scanner('1.2e3');

        $token = $scanner->scan();
        $this->assertEquals(Doctrine_Query_Token::T_NUMERIC, $token->getType());
        $this->assertEquals(1.2e3, $token->getValue());
    }

    public function testScannerRecognizesFloatWithNegativeExponent()
    {
        $scanner = new Doctrine_Query_Scanner('7E-10');

        $token = $scanner->scan();
        $this->assertEquals(Doctrine_Query_Token::T_NUMERIC, $token->getType());
        $this->assertEquals(7E-10, $token->getValue());
    }

    public function testScannerRecognizesStringContainingWhitespace()
    {
        $scanner = new Doctrine_Query_Scanner("'This is a string.'");

        $token = $scanner->scan();
        $this->assertEquals(Doctrine_Query_Token::T_STRING, $token->getType());
        $this->assertEquals("'This is a string.'", $token->getValue());
    }

    public function testScannerRecognizesStringContainingSingleQuotes()
    {
        $scanner = new Doctrine_Query_Scanner("'abc''defg'''");

        $token = $scanner->scan();
        $this->assertEquals(Doctrine_Query_Token::T_STRING, $token->getType());
        $this->assertEquals("'abc''defg'''", $token->getValue());
    }

}
