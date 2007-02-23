<?php
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('stringtest', 'string', 200, array('fixed' => true));
    }
}
?>
