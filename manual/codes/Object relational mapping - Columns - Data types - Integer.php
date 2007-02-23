<?php
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('integertest', 'integer', 4, array('unsigned' => true));
    }
}
?>
