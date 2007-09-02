<?php
class CustomSequenceRecord extends Doctrine_Record {
    public function setTableDefinition()
    {
        $this->hasColumn('id', 'integer', null, array('primary', 'sequence' => 'custom_seq'));
        $this->hasColumn('name', 'string');
    }
}

