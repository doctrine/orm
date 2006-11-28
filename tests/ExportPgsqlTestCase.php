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
    public function testCreateTableSupportsAutoincPks() {
        $name = 'mytable';
        
        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true));

        $this->export->createTable($name, $fields);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (id SERIAL PRIMARY KEY)');
    }
    public function testCreateTableSupportsDefaultAttribute() {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10, 'default' => 'def'),
                         'type' => array('type' => 'integer', 'length' => 3, 'default' => 12)
                         );
                         
        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10) DEFAULT \'def\', type INT DEFAULT 12, PRIMARY KEY(name, type))');
    }
    public function testCreateTableSupportsMultiplePks() {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10),
                         'type' => array('type' => 'integer', 'length' => 3));
                         
        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);
        
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10), type INT, PRIMARY KEY(name, type))');
    }
}
?>
