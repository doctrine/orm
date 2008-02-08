<?php
class mmrGroup_B extends Doctrine_Record
{
    public function setUp() {
        $this->hasMany('mmrUser_B', array('local' => 'group_id',
                                     'foreign' => 'user_id',
                                     'refClass' => 'mmrGroupUser_B'));
    }
    public function setTableDefinition() {
        // Works when
        $this->hasColumn('id', 'string', 30, array (  'primary' => true));
        $this->hasColumn('name', 'string', 30);
    }
}
