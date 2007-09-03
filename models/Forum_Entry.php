<?php
class Forum_Entry extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn('author', 'string', 50); 
        $this->hasColumn('topic', 'string', 100);
        $this->hasColumn('message', 'string', 99999);
        $this->hasColumn('parent_entry_id', 'integer', 10);
        $this->hasColumn('thread_id', 'integer', 10);
        $this->hasColumn('date', 'integer', 10);
    }
    public function setUp() {
        $this->hasOne('Forum_Entry as Parent',  array('local' => 'id', 'foreign' => 'parent_entry_id'));
        $this->hasOne('Forum_Thread as Thread', array('local' => 'thread_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
    }
}

