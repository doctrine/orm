<?php
class Doctrine_Export_TestCase extends Doctrine_Driver_UnitTestCase {


    public function testCreateTableThrowsExceptionWithoutValidTableName() {
        try {
            $this->export->createTable(0,array(),array());

            $this->fail();
        } catch(Doctrine_Export_Exception $e) {
            $this->pass();
        }
    }
    public function testCreateTableThrowsExceptionWithEmptyFieldsArray() {
        try {
            $this->export->createTable('sometable',array(),array());

            $this->fail();
        } catch(Doctrine_Export_Exception $e) {
            $this->pass();
        }
    }
    public function testDropConstraintExecutesSql() {
        $this->export->dropConstraint('sometable', 'relevancy');
        
        $this->assertEqual($this->adapter->pop(), 'ALTER TABLE sometable DROP CONSTRAINT relevancy');
    }
    public function testCreateIndexExecutesSql() {
        $this->export->createIndex('sometable', 'relevancy', array('fields' => array('title' => array(), 'content' => array())));
        
        $this->assertEqual($this->adapter->pop(), 'CREATE INDEX relevancy ON sometable (title, content)');
    }

    public function testDropIndexExecutesSql() {
        $this->export->dropIndex('sometable', 'relevancy');
        
        $this->assertEqual($this->adapter->pop(), 'DROP INDEX relevancy');
    }
    public function testDropTableExecutesSql() {
        $this->export->dropTable('sometable');
        
        $this->assertEqual($this->adapter->pop(), 'DROP TABLE sometable');
    }
}
?>
