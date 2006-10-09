<?php
class Doctrine_EnumTestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array("EnumTest");
        parent::prepareTables();
    }

    public function testParameterConversion() {
        $test = new EnumTest();
        $test->status = 'open';
        $this->assertEqual($test->status, 'open');
        $test->save();

        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM EnumTest WHERE EnumTest.status = ?', array('open'));
        $this->assertEqual(count($ret), 1);

        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM EnumTest WHERE EnumTest.status = open');
        $this->assertEqual(count($ret), 1);

    }
    public function testEnumType() {

        $enum = new EnumTest();
        $enum->status = "open";
        $this->assertEqual($enum->status, "open");
        $enum->save();
        $this->assertEqual($enum->status, "open");
        $enum->refresh();
        $this->assertEqual($enum->status, "open");

        $enum->status = "closed";

        $this->assertEqual($enum->status, "closed");

        $enum->save();
        $this->assertEqual($enum->status, "closed");
        $this->assertTrue(is_numeric($enum->id));
        $enum->refresh();
        $this->assertEqual($enum->status, "closed");
    }

    public function testEnumTypeWithCaseConversion() {
        $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $enum = new EnumTest();

        $enum->status = "open";
        $this->assertEqual($enum->status, "open");

        $enum->save();
        $this->assertEqual($enum->status, "open");

        $enum->refresh();
        $this->assertEqual($enum->status, "open");      
        
        $enum->status = "closed";

        $this->assertEqual($enum->status, "closed");

        $enum->save();
        $this->assertEqual($enum->status, "closed");

        $enum->refresh();
        $this->assertEqual($enum->status, "closed");
        
        $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
    }

    public function testFailingRefresh() {
        $enum = $this->connection->getTable('EnumTest')->find(1);

        $this->dbh->query('DELETE FROM enum_test WHERE id = 1');

        $f = false;
        try {
            $enum->refresh();
        } catch(Doctrine_Record_Exception $e) {
            $f = true;
        }
        $this->assertTrue($f);
    }
}
?>
