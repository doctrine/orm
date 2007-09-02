<?php
class Forum_Thread extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('board_id', 'integer', 10);
        $this->hasColumn('updated', 'integer', 10);
        $this->hasColumn('closed', 'integer', 1);
    }
    public function setUp() {
        $this->hasOne('Forum_Board as Board', 'Forum_Thread.board_id');
        $this->ownsMany('Forum_Entry as Entries', 'Forum_Entry.thread_id');
    }
}

