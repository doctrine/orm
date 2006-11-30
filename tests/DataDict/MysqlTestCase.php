<?php
class Doctrine_DataDict_Mysql_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('mysql');
    }    
    public function testGetPortableDefinitionSupportsIntegers() {
        $field = array('INT UNSIGNED');
                                                           	
    }
    public function testGetNativeDefinitionSupportsIntegerType() {
        $a = array('type' => 'integer', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BIGINT');
        
        $a['length'] = 4;

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'INT');

        $a['length'] = 2;

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'SMALLINT');
    }

    public function testGetNativeDeclarationSupportsFloatType() {
        $a = array('type' => 'float', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'DOUBLE');
    }
    public function testGetNativeDeclarationSupportsBooleanType() {
        $a = array('type' => 'boolean', 'fixed' => false);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TINYINT(1)');
    }
    public function testGetNativeDeclarationSupportsDateType() {
        $a = array('type' => 'date', 'fixed' => false);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'DATE');
    }
    public function testGetNativeDeclarationSupportsTimestampType() {
        $a = array('type' => 'timestamp', 'fixed' => false);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'DATETIME');
    }
    public function testGetNativeDeclarationSupportsTimeType() {
        $a = array('type' => 'time', 'fixed' => false);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TIME');
    }
    public function testGetNativeDeclarationSupportsClobType() {
        $a = array('type' => 'clob');

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'LONGTEXT');
    }
    public function testGetNativeDeclarationSupportsBlobType() {
        $a = array('type' => 'blob');

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'LONGBLOB');
    }
    public function testGetNativeDeclarationSupportsCharType() {
        $a = array('type' => 'char', 'length' => 10);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'CHAR(10)');
    }
    public function testGetNativeDeclarationSupportsVarcharType() {
        $a = array('type' => 'varchar', 'length' => 10);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'VARCHAR(10)');
    }
    public function testGetNativeDeclarationSupportsArrayType() {
        $a = array('type' => 'array', 'length' => 40);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'VARCHAR(40)');
    }
    public function testGetNativeDeclarationSupportsStringType() {
        $a = array('type' => 'string');

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDeclarationSupportsArrayType2() {
        $a = array('type' => 'array');

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDeclarationSupportsObjectType() {
        $a = array('type' => 'object');

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TEXT');
    }
}
