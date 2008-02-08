<?php
class Doctrine_Export_Reporter_TestCase extends Doctrine_UnitTestCase {
    public function testExportChecksClassNaming() {
        $reporter = $this->export->export('BadLyNamed__Class');

        // Class name is not valid. Double underscores are not allowed

        $this->assertEqual($reporter->pop(), array(E_WARNING, 'Badly named class.'));
    }
}
