<?php
class Doctrine_DataDict_Sqlite_TestCase extends Doctrine_Driver_UnitTestCase {
    public function getDeclaration($type) {
        return $this->dataDict->getDoctrineDeclaration(array('type' => $type, 'name' => 'colname', 'length' => 2, 'fixed' => true));
    }
    public function testGetDoctrineDefinition() {
        $this->assertEqual($this->getDeclaration('boolean'), array(array('boolean'), 1, false, null));

        $this->assertEqual($this->getDeclaration('tinyint'), array(array('integer', 'boolean'), 1, false, null));
        $this->assertEqual($this->getDeclaration('smallint'), array(array('integer'), 2, false, null));
        $this->assertEqual($this->getDeclaration('mediumint'), array(array('integer'), 2, false, null));
        $this->assertEqual($this->getDeclaration('int'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('integer'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('serial'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('bigint'), array(array('integer'), 8, false, null));
        $this->assertEqual($this->getDeclaration('bigserial'), array(array('integer'), 8, false, null));
        /**
        $this->assertEqual($this->getDeclaration('clob'), array(array('integer', 'boolean'), 1, false, null));
        $this->assertEqual($this->getDeclaration('tinytext'), array(array('integer'), 2, false, null));
        $this->assertEqual($this->getDeclaration('mediumtext'), array(array('integer'), 2, false, null));
        $this->assertEqual($this->getDeclaration('longtext'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('text'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('varchar'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('varchar2'), array(array('integer'), 8, false, null));

        $this->assertEqual($this->getDeclaration('char'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('date'), array(array('integer'), 4, false, null));
        $this->assertEqual($this->getDeclaration('datetime'), array(array('integer'), 8, false, null));
        $this->assertEqual($this->getDeclaration('timestamp'), array(array('integer'), 8, false, null));
        $this->assertEqual($this->getDeclaration('time'), array(array('integer'), 8, false, null));
        */
        $this->assertEqual($this->getDeclaration('float'), array(array('float'), 8, false, null));
        $this->assertEqual($this->getDeclaration('double'), array(array('float'), 8, false, null));
        $this->assertEqual($this->getDeclaration('real'), array(array('float'), 8, false, null));

        $this->assertEqual($this->getDeclaration('decimal'), array(array('decimal'), 8, false, null));
        $this->assertEqual($this->getDeclaration('numeric'), array(array('decimal'), 8, false, null));

        $this->assertEqual($this->getDeclaration('tinyblob'), array(array('blob'), 8, false, null));
        $this->assertEqual($this->getDeclaration('mediumblob'), array(array('blob'), 8, false, null));
        $this->assertEqual($this->getDeclaration('longblob'), array(array('blob'), 8, false, null));
        $this->assertEqual($this->getDeclaration('blob'), array(array('blob'), 8, false, null));
    }
}
