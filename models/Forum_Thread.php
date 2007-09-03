<?php
class Forum_Thread extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('board_id', 'integer', 10);
        $this->hasColumn('updated', 'integer', 10);
        $this->hasColumn('closed', 'integer', 1);
    }
    public function setUp() {
        $this->hasOne('Forum_Board as Board', array('local' => 'board_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
        $this->hasMany('Forum_Entry as Entries', array('local' => 'id', 'foreign' => 'thread_id'));
    }
}

