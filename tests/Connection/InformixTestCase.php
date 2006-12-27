<?php
class Doctrine_Connection_Informix_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('sqlite');
    }
    public function testNoSuchTableErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'no such table: test1'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }
    public function testNoSuchIndexErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'no such index: test1'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOT_FOUND);
    }
    public function testUniquePrimaryKeyErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'PRIMARY KEY must be unique'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testIsNotUniqueErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'is not unique'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testColumnsNotUniqueErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'columns name, id are not unique'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testUniquenessConstraintErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'uniqueness constraint failed'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testNotNullConstraintErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'may not be NULL'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT_NOT_NULL);
    }
    public function testNoSuchFieldErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'no such column: column1'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testColumnNotPresentInTablesErrorIsSupported2() {
        $this->exc->processErrorInfo(array(0,0, 'column not present in both tables'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testNearSyntaxErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, "near \"SELECT FROM\": syntax error"));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testValueCountOnRowErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, '3 values for 2 columns'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_VALUE_COUNT_ON_ROW);
    }
}
