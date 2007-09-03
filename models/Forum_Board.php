<?php
class Forum_Board extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn('category_id', 'integer', 10);
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('description', 'string', 5000);
    }
    public function setUp() {
        $this->hasOne('Forum_Category as Category', array('local' => 'category_id', 'foreign' => 'id'));
        $this->hasMany('Forum_Thread as Threads',  array('local' => 'id', 'foreign' => 'board_id'));
    } 
}

