<?php
class Address extends Doctrine_Record 
{
    public function setUp()
    {
        $this->hasMany('User', array('local' => 'address_id', 
                                     'foreign' => 'user_id',
                                     'refClass' => 'EntityAddress'));
    }
    public function setTableDefinition() {
        $this->hasColumn('address', 'string', 200);
    }
}
