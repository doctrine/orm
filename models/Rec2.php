<?php
class Rec2  extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('user_id', 'integer', 10, array (  'unique' => true,));
        $this->hasColumn('address', 'string', 150, array ());
    }

    public function setUp()
    {
        $this->hasOne('Rec1 as User', 'Rec2.user_id');
    }

}
