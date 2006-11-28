<?php
class Doctrine_Connection_Mysql_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('mysql');
    }
    public function testQuoteIdentifier() {
        $id = $this->conn->quoteIdentifier('identifier', false);
        $this->assertEqual($id, '`identifier`');
    }
}
