<?php
class EnumTest2 extends Doctrine_Record 
{
    public function setTableDefinition() {
        $this->hasColumn('status', 'enum', 11, array('values' => array('open', 'verified', 'closed')));
        $this->hasColumn('enum_test_id', 'integer');
    }
}
