<?php
/**
class Doctrine_DataDict_Sqlite_TestCase extends Doctrine_UnitTestCase {
    private $dict;
    
    private $columns;
    public function prepareData() { }
    public function prepareTables() {
        $this->dbh->query("CREATE TABLE test (col_null NULL,
                                              col_int  INTEGER NOT NULL,
                                              col_real REAL,
                                              col_text TEXT DEFAULT 'default' NOT NULL,
                                              col_blob BLOB)");

        $this->dict = new Doctrine_DataDict_Sqlite($this->dbh);
        $this->columns = $this->dict->listTableColumns('test');
    }
    public function testListTables() {
        $result = $this->dict->listTables();
        
    }
    public function testIntegerType() {
        $this->assertEqual($this->columns['col_int']->isUnique(), false);
        $this->assertEqual($this->columns['col_int']->isNotNull(), true);
        $this->assertEqual($this->columns['col_int']->defaultValue(), null);
        $this->assertEqual($this->columns['col_int']->isPrimaryKey(), false);
        $this->assertEqual($this->columns['col_int']->getType(), 'INTEGER');
        $this->assertEqual($this->columns['col_int']->getName(), 'col_int');
    }
    public function testNullType() {
        $this->assertEqual($this->columns['col_null']->isUnique(), false);
        $this->assertEqual($this->columns['col_null']->isNotNull(), false);
        $this->assertEqual($this->columns['col_null']->defaultValue(), null);
        $this->assertEqual($this->columns['col_null']->isPrimaryKey(), false);
        $this->assertEqual($this->columns['col_null']->getType(), 'numeric');
        $this->assertEqual($this->columns['col_null']->getName(), 'col_null');
    }
    public function testTextType() {
        $this->assertEqual($this->columns['col_text']->isUnique(), false);
        $this->assertEqual($this->columns['col_text']->isNotNull(), true);
        $this->assertEqual($this->columns['col_text']->defaultValue(), 'default');
        $this->assertEqual($this->columns['col_text']->isPrimaryKey(), false);
        $this->assertEqual($this->columns['col_text']->getType(), 'TEXT');
        $this->assertEqual($this->columns['col_text']->getName(), 'col_text');
    }
    public function testBlobType() {
        $this->assertEqual($this->columns['col_blob']->isUnique(), false);
        $this->assertEqual($this->columns['col_blob']->isNotNull(), false);
        $this->assertEqual($this->columns['col_blob']->defaultValue(), null);
        $this->assertEqual($this->columns['col_blob']->isPrimaryKey(), false);
        $this->assertEqual($this->columns['col_blob']->getType(), 'BLOB');
        $this->assertEqual($this->columns['col_blob']->getName(), 'col_blob');
    }        
    public function testRealType() {
        $this->assertEqual($this->columns['col_real']->isUnique(), false);
        $this->assertEqual($this->columns['col_real']->isNotNull(), false);
        $this->assertEqual($this->columns['col_real']->defaultValue(), null);
        $this->assertEqual($this->columns['col_real']->isPrimaryKey(), false);
        $this->assertEqual($this->columns['col_real']->getType(), 'REAL');
        $this->assertEqual($this->columns['col_real']->getName(), 'col_real');
    }
}
*/
