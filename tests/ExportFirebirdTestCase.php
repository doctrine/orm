<?php
class Doctrine_Export_Firebird_TestCase extends Doctrine_Export_TestCase {
    public function __construct() {
        parent::__construct('firebird');
    }
    public function testCreateDatabaseDoesNotExecuteSql() {
        try {
            $this->export->createDatabase('db');
            $this->fail();
        } catch(Doctrine_Export_Firebird_Exception $e) {
            $this->pass();
        }
    }
    public function testDropDatabaseDoesNotExecuteSql() {
        try {
            $this->export->dropDatabase('db');
            $this->fail();
        } catch(Doctrine_Export_Firebird_Exception $e) {
            $this->pass();
        }
    }

}
?>
