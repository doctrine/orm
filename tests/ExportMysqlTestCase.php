<?php
class Doctrine_Export_Mysql_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('mysql');
    }

    public function testAlterTableThrowsExceptionWithoutValidTableName() {
        try {
            $this->export->alterTable(0,0,array());

            $this->fail();
        } catch(Doctrine_Export_Exception $e) {
            $this->pass();
        }
    }
    public function testCreateTableExecutesSql() {
        $name = 'mytable';
        
        $fields = array('id' => array('type' => 'integer', 'unsigned' => 1));
        
        $options = array('type' => 'foo');
        
        //$this->export->createTable($name, $fields, $options);
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
