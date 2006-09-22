<?php
class Doctrine_EnumTestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array("EnumTest");
        parent::prepareTables();
    }

    public function testSet() {
        $test = new EnumTest();
        $test->status = 'open';
        $this->assertEqual($test->status, 'open');
        $test->save();

        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM EnumTest WHERE EnumTest.status =  ?', array('open'));
        $this->assertEqual(count($ret), 1);

        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM EnumTest WHERE EnumTest.status = "open"');
        $this->assertEqual(count($ret), 1);


    }
}
?>
