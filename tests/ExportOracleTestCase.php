<?php
class Doctrine_Export_Oracle_TestCase extends Doctrine_Export_TestCase {
    public function __construct() {
        parent::__construct('oci');
    }
    public function testCreateSequenceExecutesSql() {
        $sequenceName = 'sequence';
        $start = 1;
        $query = 'CREATE SEQUENCE ' . $sequenceName . '_seq START WITH ' . $start . ' INCREMENT BY 1 NOCACHE';

        $this->export->createSequence($sequenceName, $start);
        
        $this->assertEqual($this->adapter->pop(), $query);
    }

    public function testDropSequenceExecutesSql() {
        $sequenceName = 'sequence';

        $query = 'DROP SEQUENCE ' . $sequenceName;

        $this->export->dropSequence($sequenceName);
        
        $this->assertEqual($this->adapter->pop(), $query . '_seq');
    }
    public function testCreateTableExecutesSql() {
        $name = 'mytable';
        
        $fields  = array('id' => array('type' => 'integer'));
        $options = array('type' => 'MYISAM');
        
        $this->export->createTable($name, $fields);

        $this->assertEqual($this->adapter->pop(), 'COMMIT');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (id INT)');
        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');
    }
    public function testCreateTableSupportsDefaultAttribute() {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10, 'default' => 'def'),
                         'type' => array('type' => 'integer', 'length' => 3, 'default' => 12)
                         );
                         
        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);
        

        $this->assertEqual($this->adapter->pop(), 'COMMIT');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10) DEFAULT \'def\', type NUMBER(3) DEFAULT 12, PRIMARY KEY(name, type))');
        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');
    }
    public function testCreateTableSupportsMultiplePks() {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10),
                         'type' => array('type' => 'integer', 'length' => 3));
                         
        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);
        

        $this->assertEqual($this->adapter->pop(), 'COMMIT');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10), type NUMBER(3), PRIMARY KEY(name, type))');
        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');
    }
    public function testCreateTableSupportsAutoincPks() {
        $name = 'mytable';
        
        $fields  = array('id' => array('type' => 'integer', 'autoincrement' => true));

        
        $this->export->createTable($name, $fields);

        $this->assertEqual($this->adapter->pop(), 'COMMIT');
        $this->assertEqual(substr($this->adapter->pop(),0, 14), 'CREATE TRIGGER');
        $this->assertEqual($this->adapter->pop(), 'CREATE SEQUENCE MYTABLE_seq START WITH 1 INCREMENT BY 1 NOCACHE');  
        $this->assertEqual($this->adapter->pop(), 'ALTER TABLE MYTABLE ADD CONSTRAINT MYTABLE_AI_PK_idx PRIMARY KEY (id)');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (id INT)');
        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');
    }

    public function testCreateTableSupportsCharType() {
        $name = 'mytable';
        
        $fields  = array('id' => array('type' => 'char', 'length' => 3));

        $this->export->createTable($name, $fields);

        $this->adapter->pop();
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (id CHAR(3))');
    }
}
?>
