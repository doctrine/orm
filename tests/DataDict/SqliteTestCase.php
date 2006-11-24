<?php
class Doctrine_DataDict_Sqlite_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('sqlite');
    }

    public function testBooleanMapsToBooleanType() {
        $this->assertDeclarationType('boolean', 'boolean');
    }
    public function testIntegersMapToIntegerType() {
        $this->assertDeclarationType('tinyint', array('integer', 'boolean'));
        $this->assertDeclarationType('smallint', 'integer');
        $this->assertDeclarationType('mediumint', 'integer');
        $this->assertDeclarationType('int', 'integer');
        $this->assertDeclarationType('integer', 'integer');
        $this->assertDeclarationType('serial', 'integer');
        $this->assertDeclarationType('bigint', 'integer');
        $this->assertDeclarationType('bigserial', 'integer');
    }
    public function testBlobsMapToBlobType( ){
        $this->assertDeclarationType('tinyblob', 'blob');
        $this->assertDeclarationType('mediumblob', 'blob');
        $this->assertDeclarationType('longblob', 'blob');
        $this->assertDeclarationType('blob', 'blob');
    }
    public function testDecimalMapsToDecimal() {
        $this->assertDeclarationType('decimal', 'decimal');
        $this->assertDeclarationType('numeric', 'decimal');
    }
    public function testFloatRealAndDoubleMapToFloat() {
        $this->assertDeclarationType('float', 'float');
        $this->assertDeclarationType('double', 'float');
        $this->assertDeclarationType('real', 'float');
    }
    public function testYearMapsToIntegerAndDate() {
         $this->assertDeclarationType('year', array('integer','date'));
    }
    public function testSomething( ){
        /**

        $this->assertEqual($this->getDeclaration('clob'), array(array('integer', 'boolean'), 1, false, null));
        $this->assertEqual($this->getDeclaration('tinytext'), array(array('integer'), 2, false, null));
        $this->assertEqual($this->getDeclaration('mediumtext'), array(array('integer'), 2, false, null));
        $this->assertEqual($this->getDeclaration('longtext'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('text'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('varchar'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('varchar2'), array(array('integer'), 8, false, null));

        $this->assertEqual($this->getDeclaration('char'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('date'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('datetime'), array(array('integer'), 8, false, null));
        $this->assertEqual($this->getDeclaration('timestamp'), array(array('integer'), 8, false, null));
        $this->assertEqual($this->getDeclaration('time'), array(array('integer'), 8, false, null));

                */
    }
}
