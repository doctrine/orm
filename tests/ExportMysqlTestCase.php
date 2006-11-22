<?php
class Doctrine_Export_Mysql_TestCase extends Doctrine_Export_TestCase {
    public function __construct() {
        parent::__construct('mysql');
    } 
    public function testCreateDatabaseExecutesSql() {
        $this->export->createDatabase('db');

        $this->assertEqual($this->adapter->pop(), 'CREATE DATABASE db');
    }
    public function testDropDatabaseExecutesSql() {
        $this->export->dropDatabase('db');

        $this->assertEqual($this->adapter->pop(), 'DROP DATABASE db');
    }

    public function testDropIndexExecutesSql() {
        $this->export->dropIndex('sometable', 'relevancy');
        
        $this->assertEqual($this->adapter->pop(), 'DROP INDEX relevancy ON sometable');
    }
}
?>
