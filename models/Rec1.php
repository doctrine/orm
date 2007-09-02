<?php
class Rec1 extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('first_name', 'string', 128, array ());
    }

    public function setUp()
    {
        $this->ownsOne('Rec2 as Account', array('local' => 'id', 'foreign' => 'user_id'));
    }
}


