<?php
class Doctrine_BooleanTestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array("BooleanTest");
        parent::prepareTables();
    }
    public function testSetFalse() {
        $test = new BooleanTest();
        $test->is_working = false;

        $this->assertEqual($test->is_working, false);
        $this->assertEqual($test->getState(), Doctrine_Record::STATE_TDIRTY);
        $test->save();

        $test->refresh();
        $this->assertEqual($test->is_working, false);
    }

    public function testSetTrue() {
        $test = new BooleanTest();
        $test->is_working = true;
        $this->assertEqual($test->is_working, true);
        $test->save();
        
        $test->refresh();
        $this->assertEqual($test->is_working, true);
        
        $this->connection->clear();
        
        $test = $test->getTable()->find($test->id);
        $this->assertEqual($test->is_working, true);
    }

    public function testFetching() {

        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM BooleanTest WHERE BooleanTest.is_working =  ?', array(false));
        $this->assertEqual(count($ret), 1);

        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM BooleanTest WHERE BooleanTest.is_working =  ?', array(true));
        $this->assertEqual(count($ret), 1);
    }

}
?>
