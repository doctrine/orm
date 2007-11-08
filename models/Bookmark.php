<?php
class Bookmark extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('user_id', 'integer', null, array('primary' => true));
        $this->hasColumn('page_id', 'integer', null, array('primary' => true));
    }
}
