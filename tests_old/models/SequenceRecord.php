<?php
class SequenceRecord extends Doctrine_Record {
    public function setTableDefinition()
    {
        $this->hasColumn('id', 'integer', null, array('primary', 'sequence'));
        $this->hasColumn('name', 'string');
    }
}
