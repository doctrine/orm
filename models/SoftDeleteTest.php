<?php
class SoftDeleteTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', null, array('primary' => true));
        $this->hasColumn('something', 'string', '25', array('notnull' => true, 'unique' => true));
        $this->hasColumn('deleted', 'boolean', 1);
    }
    public function preDelete($event)
    {
        $event->skipOperation();
    }
    public function postDelete($event)
    {
        $this->deleted = true;

        $this->save();
    }
}
