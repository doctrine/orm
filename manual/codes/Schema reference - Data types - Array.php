<?php
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('arraytest', 'array', 10000);
    }
}
?>
