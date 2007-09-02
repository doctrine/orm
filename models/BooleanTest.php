<?php
class BooleanTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('is_working', 'boolean');
        $this->hasColumn('is_working_notnull', 'boolean', 1, array('default' => false, 'notnull' => true));
    }
}
