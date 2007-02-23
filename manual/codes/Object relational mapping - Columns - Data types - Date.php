<?php
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('datetest', 'date');
    }
}
?>
