<?php
class Doctrine_DataDict_Pgsql_TestCase extends Doctrine_UnitTestCase {


    public function getDeclaration($type) {
        return $this->dataDict->getPortableDeclaration(array('type' => $type, 'name' => 'colname', 'length' => 2, 'fixed' => true));
    }
    public function testGetPortableDeclarationSupportsIntegers() {
        $this->assertEqual($this->getDeclaration('smallint'), array(array('integer', 'boolean'), 2, false, null));
        $this->assertEqual($this->getDeclaration('int2'), array(array('integer', 'boolean'), 2, false, null));

        $this->assertEqual($this->getDeclaration('int'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('int4'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('integer'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('serial'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('serial4'), array(array('integer'), 4, false, null));

        $this->assertEqual($this->getDeclaration('bigint'), array(array('integer'), 8, false, null));
        $this->assertEqual($this->getDeclaration('int8'), array(array('integer'), 8, false, null));
        $this->assertEqual($this->getDeclaration('bigserial'), array(array('integer'), 8, false, null));
        $this->assertEqual($this->getDeclaration('serial8'), array(array('integer'), 8, false, null));
    }
    public function testGetPortableDeclarationSupportsBooleans() {
        $this->assertEqual($this->getDeclaration('bool'), array(array('boolean'), 1, false, null));
        $this->assertEqual($this->getDeclaration('boolean'), array(array('boolean'), 1, false, null));
    }

    public function testGetNativeDefinitionSupportsIntegerType() {
        $a = array('type' => 'integer', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BIGINT');
        
        $a['length'] = 4;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INT');

        $a['length'] = 2;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'SMALLINT');
    }
    public function testGetNativeDefinitionSupportsIntegerTypeWithAutoinc() {
        $a = array('type' => 'integer', 'length' => 20, 'fixed' => false, 'autoincrement' => true);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BIGSERIAL PRIMARY KEY');

        $a['length'] = 4;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'SERIAL PRIMARY KEY');

        $a['length'] = 2;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'SERIAL PRIMARY KEY');
    }
    public function testGetNativeDefinitionSupportsFloatType() {
        $a = array('type' => 'float', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'FLOAT8');
    }
    public function testGetNativeDefinitionSupportsBooleanType() {
        $a = array('type' => 'boolean', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BOOLEAN');
    }
    public function testGetNativeDefinitionSupportsDateType() {
        $a = array('type' => 'date', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'DATE');
    }
    public function testGetNativeDefinitionSupportsTimestampType() {
        $a = array('type' => 'timestamp', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TIMESTAMP without time zone');
    }
    public function testGetNativeDefinitionSupportsTimeType() {
        $a = array('type' => 'time', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TIME without time zone');
    }
    public function testGetNativeDefinitionSupportsClobType() {
        $a = array('type' => 'clob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDefinitionSupportsBlobType() {
        $a = array('type' => 'blob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BYTEA');
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
}
