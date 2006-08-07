<?php
require_once("UnitTestCase.php");

class Doctrine_Filter_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }

    public function prepareTables() {
        $this->tables = array("FilterTest","FilterTest2");
    }
    public function testOperations() {
        $t = new FilterTest;
    }
}
?>
