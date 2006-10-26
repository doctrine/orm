<?php
class Doctrine_DataDict_Pgsql_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() { }
    public function prepareData() { }
    public function getDeclaration($type) {
        return $this->dict->getDoctrineDeclaration(array('type' => $type, 'name' => 'colname', 'length' => 2, 'fixed' => true));
    }
    public function testGetDoctrineDefinition() {
        $this->dict = new Doctrine_DataDict_Pgsql();

        $this->assertEqual($this->getDeclaration('smallint'), array(array('integer', 'boolean'), 2, false, null));
        $this->assertEqual($this->getDeclaration('int2'), array(array('integer', 'boolean'), 2, false, null));

        $this->assertEqual($this->getDeclaration('int'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('int4'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('integer'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('serial'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('serial4'), array(array('integer'), 4, false, null));
        

        $this->assertEqual($this->getDeclaration('bigint'), array(array('integer'), 8, false, null));
        $this->assertEqual($this->getDeclaration('int8'), array(array('integer'), 8, false, null));
        $this->assertEqual($this->getDeclaration('bigserial'), array(array('integer'), 8, false, null));
        $this->assertEqual($this->getDeclaration('serial8'), array(array('integer'), 8, false, null));

        $this->assertEqual($this->getDeclaration('bool'), array(array('boolean'), 1, false, null));
        $this->assertEqual($this->getDeclaration('boolean'), array(array('boolean'), 1, false, null));

    }
}
