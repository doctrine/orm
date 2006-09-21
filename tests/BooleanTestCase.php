<?php
class Doctrine_BooleanTestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array("BooleanTest");
        parent::prepareTables();
    }

    public function testSet() {
        $test = new BooleanTest();
        $test->is_working = true;
        $this->assertEqual($test->is_working, true);
        $test->save();

        $test = new BooleanTest();
        $test->is_working = true;
        $test->save();

        $test = new BooleanTest();
        $this->is_working = false;
        $this->assertEqual($test->is_working, false);
        $test->save();

        $test = new BooleanTest();
        $this->is_working = false;
        $test->save();

        $test = new BooleanTest();
        $this->is_working = false;
        $test->save();

        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM BooleanTest WHERE BooleanTest.is_working =  ?', array(false));
        $this->assertEqual(count($ret), 3);

        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM BooleanTest WHERE BooleanTest.is_working =  ?', array(true));
        $this->assertEqual(count($ret), 2);


    }
}
?>
