<?php
class Forum_Board extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn('category_id', 'integer', 10);
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('description', 'string', 5000);
    }
    public function setUp() {
        $this->hasOne('Forum_Category as Category', 'Forum_Board.category_id');
        $this->ownsMany('Forum_Thread as Threads',  'Forum_Thread.board_id');
    } 
}

