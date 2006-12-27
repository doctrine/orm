<?php
class Doctrine_Connection_Pgsql_TestCase extends Doctrine_UnitTestCase {
    public function __construct() {
        parent::__construct('pgsql');
    }
    public function testNoSuchTableErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'table test does not exist')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }
    public function testSyntaxErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'parser: parse error at or near')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testSyntaxErrorIsSupported2() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'syntax error at')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testSyntaxErrorIsSupported3() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'column reference r.r is ambiguous')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testInvalidNumberErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'pg_atoi: error in somewhere: can\'t parse ')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID_NUMBER);
    }
    public function testInvalidNumberErrorIsSupported2() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'value unknown is out of range for type bigint')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID_NUMBER);
    }
    public function testInvalidNumberErrorIsSupported3() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'integer out of range')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID_NUMBER);
    }
    public function testInvalidNumberErrorIsSupported4() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'invalid input syntax for type integer')));

        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID_NUMBER);
    }
    /**
                                    '/column .* (of relation .*)?does not exist/i'
                                        => Doctrine::ERR_NOSUCHFIELD,
                                    '/attribute .* not found|relation .* does not have attribute/i'
                                        => Doctrine::ERR_NOSUCHFIELD,
                                    '/column .* specified in USING clause does not exist in (left|right) table/i'
                                        => Doctrine::ERR_NOSUCHFIELD,
                                    '/(relation|sequence|table).*does not exist|class .* not found/i'
                                        => Doctrine::ERR_NOSUCHTABLE,
                                    '/index .* does not exist/'
                                        => Doctrine::ERR_NOT_FOUND,
                                    '/relation .* already exists/i'
                                        => Doctrine::ERR_ALREADY_EXISTS,
                                    '/(divide|division) by zero$/i'
                                        => Doctrine::ERR_DIVZERO,
                                    '/value too long for type character/i'
                                        => Doctrine::ERR_INVALID,
                                    '/permission denied/'
                                        => Doctrine::ERR_ACCESS_VIOLATION,
                                    '/violates [\w ]+ constraint/'
                                        => Doctrine::ERR_CONSTRAINT,
                                    '/referential integrity violation/'
                                        => Doctrine::ERR_CONSTRAINT,
                                    '/violates not-null constraint/'
                                        => Doctrine::ERR_CONSTRAINT_NOT_NULL,
                                    '/more expressions than target columns/i'
                                        => Doctrine::ERR_VALUE_COUNT_ON_ROW,



    public function testNoSuchFieldErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 904, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testConstraintErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 1, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testConstraintErrorIsSupported2() {
        $this->exc->processErrorInfo(array(0, 2291, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testConstraintErrorIsSupported3() {
        $this->exc->processErrorInfo(array(0, 2449, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testConstraintErrorIsSupported4() {
        $this->exc->processErrorInfo(array(0, 2292, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testNoSuchTableErrorIsSupported4() {
        $this->exc->processErrorInfo(array(0, 2289, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }

    public function testDivZeroErrorIsSupported1() {
        $this->exc->processErrorInfo(array(0, 1476, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_DIVZERO);
    }
    public function testNotFoundErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 1418, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOT_FOUND);
    }
    public function testNotNullConstraintErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 1400, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT_NOT_NULL);
    }
    public function testNotNullConstraintErrorIsSupported2() {
        $this->exc->processErrorInfo(array(0, 1407, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT_NOT_NULL);
    }
    public function testInvalidErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 1401, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID);
    }
    public function testAlreadyExistsErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 955, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_ALREADY_EXISTS);
    }
    public function testValueCountOnRowErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 913, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_VALUE_COUNT_ON_ROW);
    }
    */
}
