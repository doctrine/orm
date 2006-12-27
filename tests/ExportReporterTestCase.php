<?php
class BadLyNamed__Class extends Doctrine_Record {
    public function setTableDefinition() {
                                         	
    }
    public function setUp() { }
}
class Doctrine_Export_Reporter_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('sqlite');
    }
    public function testExportChecksClassNaming() {
        $reporter = $this->export->export('BadLyNamed__Class');

        // Class name is not valid. Double underscores are not allowed

        $this->assertEqual($reporter->pop(), array(E_WARNING, 'Badly named class.'));
    }
    public function testExportReportsExceptions() {

        $reporter = $this->export->export('User');
        // Class name is not valid. Double underscores are not allowed

        $this->assertEqual($reporter->pop(), array(E_WARNING, Doctrine::ERR_CLASS_NAME));
    }
}
