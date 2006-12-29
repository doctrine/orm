<?php
class Doctrine_DataDict_Sqlite_TestCase extends Doctrine_UnitTestCase {
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
    public function testGetNativeDefinitionSupportsIntegerType() {
        $a = array('type' => 'integer', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INTEGER');

        $a['length'] = 4;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INTEGER');

        $a['length'] = 2;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INTEGER');
    }

    public function testGetNativeDefinitionSupportsFloatType() {
        $a = array('type' => 'float', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'DOUBLE');
    }
    public function testGetNativeDefinitionSupportsBooleanType() {
        $a = array('type' => 'boolean', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INTEGER');
    }
    public function testGetNativeDefinitionSupportsDateType() {
        $a = array('type' => 'date', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'DATE');
    }
    public function testGetNativeDefinitionSupportsTimestampType() {
        $a = array('type' => 'timestamp', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'DATETIME');
    }
    public function testGetNativeDefinitionSupportsTimeType() {
        $a = array('type' => 'time', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TIME');
    }
    public function testGetNativeDefinitionSupportsClobType() {
        $a = array('type' => 'clob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'LONGTEXT');
    }
    public function testGetNativeDefinitionSupportsBlobType() {
        $a = array('type' => 'blob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'LONGBLOB');
    }
    public function testGetNativeDefinitionSupportsCharType() {
        $a = array('type' => 'char', 'length' => 10);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'CHAR(10)');
    }
    public function testGetNativeDefinitionSupportsVarcharType() {
        $a = array('type' => 'varchar', 'length' => 10);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(10)');
    }
    public function testGetNativeDefinitionSupportsArrayType() {
        $a = array('type' => 'array', 'length' => 40);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(40)');
    }
    public function testGetNativeDefinitionSupportsStringType() {
        $a = array('type' => 'string');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDefinitionSupportsArrayType2() {
        $a = array('type' => 'array');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDefinitionSupportsObjectType() {
        $a = array('type' => 'object');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TEXT');
    }
    public function testSomething() {
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
