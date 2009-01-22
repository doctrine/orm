<?php
class Orm_Query_ScannerTest extends Doctrine_OrmTestCase
{
    public function testScannerRecognizesIdentifierWithLengthOfOneCharacter()
    {
        $scanner = new Doctrine_Query_Scanner('u');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_IDENTIFIER, $token['type']);
        $this->assertEquals('u', $token['value']);
    }

    public function testScannerRecognizesIdentifierConsistingOfLetters()
    {
        $scanner = new Doctrine_Query_Scanner('someIdentifier');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_IDENTIFIER, $token['type']);
        $this->assertEquals('someIdentifier', $token['value']);
    }

    public function testScannerRecognizesIdentifierIncludingDigits()
    {
        $scanner = new Doctrine_Query_Scanner('s0m31d3nt1f13r');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_IDENTIFIER, $token['type']);
        $this->assertEquals('s0m31d3nt1f13r', $token['value']);
    }

    public function testScannerRecognizesIdentifierIncludingUnderscore()
    {
        $scanner = new Doctrine_Query_Scanner('some_identifier');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_IDENTIFIER, $token['type']);
        $this->assertEquals('some_identifier', $token['value']);
    }

    public function testScannerRecognizesDecimalInteger()
    {
        $scanner = new Doctrine_Query_Scanner('1234');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_INTEGER, $token['type']);
        $this->assertEquals(1234, $token['value']);
    }

    public function testScannerRecognizesFloat()
    {
        $scanner = new Doctrine_Query_Scanner('1.234');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEquals(1.234, $token['value']);
    }

    public function testScannerRecognizesFloatWithExponent()
    {
        $scanner = new Doctrine_Query_Scanner('1.2e3');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEquals(1.2e3, $token['value']);
    }

    public function testScannerRecognizesFloatWithExponent2()
    {
        $scanner = new Doctrine_Query_Scanner('0.2e3');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEquals(.2e3, $token['value']);
    }

    public function testScannerRecognizesFloatWithNegativeExponent()
    {
        $scanner = new Doctrine_Query_Scanner('7E-10');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEquals(7E-10, $token['value']);
    }

    public function testScannerRecognizesFloatBig()
    {
        $scanner = new Doctrine_Query_Scanner('1,234,567.89');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEquals(1.23456789e6, $token['value']);
    }

    public function testScannerRecognizesFloatBigWrongPoint()
    {
        $scanner = new Doctrine_Query_Scanner('12,34,56,7.89');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEquals(1.23456789e6, $token['value']);
    }

    public function testScannerRecognizesFloatLocaleSpecific()
    {
        $scanner = new Doctrine_Query_Scanner('1,234');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEquals(1.234, $token['value']);
    }

    public function testScannerRecognizesFloatLocaleSpecificBig()
    {
        $scanner = new Doctrine_Query_Scanner('1.234.567,89');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEquals(1.23456789e6, $token['value']);
    }

    public function testScannerRecognizesFloatLocaleSpecificBigWrongPoint()
    {
        $scanner = new Doctrine_Query_Scanner('12.34.56.7,89');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEquals(1.23456789e6, $token['value']);
    }

    public function testScannerRecognizesFloatLocaleSpecificExponent()
    {
        $scanner = new Doctrine_Query_Scanner('1,234e2');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEquals(1.234e2, $token['value']);
    }

    public function testScannerRecognizesFloatLocaleSpecificExponent2()
    {
        $scanner = new Doctrine_Query_Scanner('0,234e2');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertEquals(.234e2, $token['value']);
    }

    public function testScannerRecognizesFloatContainingWhitespace()
    {
        $scanner = new Doctrine_Query_Scanner('-   1.234e2');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_NONE, $token['type']);
        $this->assertEquals('-', $token['value']);

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_FLOAT, $token['type']);
        $this->assertNotEquals(-1.234e2, $token['value']);
        $this->assertEquals(1.234e2, $token['value']);
    }

    public function testScannerRecognizesStringContainingWhitespace()
    {
        $scanner = new Doctrine_Query_Scanner("'This is a string.'");

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_STRING, $token['type']);
        $this->assertEquals("'This is a string.'", $token['value']);
    }

    public function testScannerRecognizesStringContainingSingleQuotes()
    {
        $scanner = new Doctrine_Query_Scanner("'abc''defg'''");

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_STRING, $token['type']);
        $this->assertEquals("'abc''defg'''", $token['value']);
    }

    public function testScannerRecognizesInputParameter()
    {
        $scanner = new Doctrine_Query_Scanner('?');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_INPUT_PARAMETER, $token['type']);
        $this->assertEquals('?', $token['value']);
    }

    public function testScannerRecognizesNamedInputParameter()
    {
        $scanner = new Doctrine_Query_Scanner(':name');

        $token = $scanner->next();
        $this->assertEquals(Doctrine_Query_Token::T_INPUT_PARAMETER, $token['type']);
        $this->assertEquals(':name', $token['value']);
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
            $this->assertEquals($expected['value'], $actual['value']);
            $this->assertEquals($expected['type'], $actual['type']);
            $this->assertEquals($expected['position'], $actual['position']);
        }

        $this->assertNull($scanner->next());
    }
}
