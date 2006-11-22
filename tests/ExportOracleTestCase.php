<?php
class Doctrine_Export_Oracle_TestCase extends Doctrine_Export_TestCase {
    public function __construct() {
        parent::__construct('oci');
    }
    public function testCreateSequenceExecutesSql() {
        $sequenceName = 'sequence';
        $start = 1;
        $query = 'CREATE SEQUENCE ' . $sequenceName . ' START WITH ' . $start . ' INCREMENT BY 1 NOCACHE';

        $this->export->createSequence($sequenceName, $start);
        
        $this->assertEqual($this->adapter->pop(), $query);
    }

    public function testDropSequenceExecutesSql() {
        $sequenceName = 'sequence';

        $query = 'DROP SEQUENCE ' . $sequenceName;;

        $this->export->dropSequence($sequenceName);
        
        $this->assertEqual($this->adapter->pop(), $query);
    }
}
?>
