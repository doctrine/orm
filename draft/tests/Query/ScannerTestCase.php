<?php
class Doctrine_Query_Scanner_TestCase extends Doctrine_UnitTestCase
{
    public function testScannerRecognizesIdentifierWithLengthOfOneCharacter()
    {
        $scanner = new Doctrine_Query_Scanner('u');

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_IDENTIFIER, $token['type']);
        $this->assertEqual('u', $token['value']);
    }

    public function testScannerRecognizesIdentifierConsistingOfLetters()
    {
        $scanner = new Doctrine_Query_Scanner('someIdentifier');

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_IDENTIFIER, $token['type']);
        $this->assertEqual('someIdentifier', $token['value']);
    }

    public function testScannerRecognizesIdentifierIncludingDigits()
    {
        $scanner = new Doctrine_Query_Scanner('s0m31d3nt1f13r');

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_IDENTIFIER, $token['type']);
        $this->assertEqual('s0m31d3nt1f13r', $token['value']);
    }

    public function testScannerRecognizesIdentifierIncludingUnderscore()
    {
        $scanner = new Doctrine_Query_Scanner('some_identifier');

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_IDENTIFIER, $token['type']);
        $this->assertEqual('some_identifier', $token['value']);
    }

    public function testScannerRecognizesDecimalInteger()
    {
        $scanner = new Doctrine_Query_Scanner('1234');

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_INTEGER, $token['type']);
        $this->assertEqual(1234, $token['value']);
    }

    public function testScannerRecognizesFloat()
    {
        $scanner = new Doctrine_Query_Scanner('1.234');

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEqual(1.234, $token['value']);
    }

    public function testScannerRecognizesFloatWithExponent()
    {
        $scanner = new Doctrine_Query_Scanner('1.2e3');

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEqual(1.2e3, $token['value']);
    }

    public function testScannerRecognizesFloatWithNegativeExponent()
    {
        $scanner = new Doctrine_Query_Scanner('7E-10');

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEqual(7E-10, $token['value']);
    }

    public function testScannerRecognizesStringContainingWhitespace()
    {
        $scanner = new Doctrine_Query_Scanner("'This is a string.'");

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_STRING, $token['type']);
        $this->assertEqual("'This is a string.'", $token['value']);
    }

    public function testScannerRecognizesStringContainingSingleQuotes()
    {
        $scanner = new Doctrine_Query_Scanner("'abc''defg'''");

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_STRING, $token['type']);
        $this->assertEqual("'abc''defg'''", $token['value']);
    }

    public function testScannerRecognizesInputParameter()
    {
        $scanner = new Doctrine_Query_Scanner('?');

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_INPUT_PARAMETER, $token['type']);
        $this->assertEqual('?', $token['value']);
    }

    public function testScannerRecognizesNamedInputParameter()
    {
        $scanner = new Doctrine_Query_Scanner(':name');

        $token = $scanner->next();
        $this->assertEqual(Doctrine_Query_Token::T_INPUT_PARAMETER, $token['type']);
        $this->assertEqual(':name', $token['value']);
    }

    public function testScannerTokenizesASimpleQueryCorrectly()
    {
        $dql = "SELECT u.* FROM User u WHERE u.name = 'Jack O''Neil'";
        $scanner = new Doctrine_Query_Scanner($dql);

        $tokens = array(
            array(
                'value' => 'SELECT',
                'type'  => Doctrine_Query_Token::T_SELECT,
                'position' => 0
            ),
            array(
                'value' => 'u',
                'type'  => Doctrine_Query_Token::T_IDENTIFIER,
                'position' => 7
            ),
            array(
                'value' => '.',
                'type'  => Doctrine_Query_Token::T_NONE,
                'position' => 8
            ),
            array(
                'value' => '*',
                'type'  => Doctrine_Query_Token::T_NONE,
                'position' => 9
            ),
            array(
                'value' => 'FROM',
                'type'  => Doctrine_Query_Token::T_FROM,
                'position' => 11
            ),
            array(
                'value' => 'User',
                'type'  => Doctrine_Query_Token::T_IDENTIFIER,
                'position' => 16
            ),
            array(
                'value' => 'u',
                'type'  => Doctrine_Query_Token::T_IDENTIFIER,
                'position' => 21
            ),
            array(
                'value' => 'WHERE',
                'type'  => Doctrine_Query_Token::T_WHERE,
                'position' => 23
            ),
            array(
                'value' => 'u',
                'type'  => Doctrine_Query_Token::T_IDENTIFIER,
                'position' => 29
            ),
            array(
                'value' => '.',
                'type'  => Doctrine_Query_Token::T_NONE,
                'position' => 30
            ),
            array(
                'value' => 'name',
                'type'  => Doctrine_Query_Token::T_IDENTIFIER,
                'position' => 31
            ),
            array(
                'value' => '=',
                'type'  => Doctrine_Query_Token::T_NONE,
                'position' => 36
            ),
            array(
                'value' => "'Jack O''Neil'",
                'type'  => Doctrine_Query_Token::T_STRING,
                'position' => 38
            )
        );

        foreach ($tokens as $expected) {
            $actual = $scanner->next();
            $this->assertEqual($expected['value'], $actual['value']);
            $this->assertEqual($expected['type'], $actual['type']);
            $this->assertEqual($expected['position'], $actual['position']);
        }

        $this->assertNull($scanner->next());
    }
}
