<?php
class EnumTest3 extends Doctrine_Record 
{
    public function setTableDefinition() {
        $this->hasColumn('text', 'string', 10, array('primary' => true));
    }
}
