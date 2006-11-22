<?php
class Doctrine_Export_Pgsql_TestCase extends Doctrine_Export_TestCase {
    public function __construct() {
        parent::__construct('pgsql');
    }
    public function testCreateDatabaseExecutesSql() {
        $this->export->createDatabase('db');

        $this->assertEqual($this->adapter->pop(), 'CREATE DATABASE db');
    }
    public function testDropDatabaseExecutesSql() {
        $this->export->dropDatabase('db');

        $this->assertEqual($this->adapter->pop(), 'DROP DATABASE db');
    }

}
?>
