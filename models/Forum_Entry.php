<?php
class Forum_Entry extends Doctrine_Record { 
    public static function initMetadata($class) {
        $class->setColumn('author', 'string', 50); 
        $class->setColumn('topic', 'string', 100);
        $class->setColumn('message', 'string', 99999);
        $class->setColumn('parent_entry_id', 'integer', 10);
        $class->setColumn('thread_id', 'integer', 10);
        $class->setColumn('date', 'integer', 10);
        $class->hasOne('Forum_Entry as Parent',  array('local' => 'id', 'foreign' => 'parent_entry_id'));
        $class->hasOne('Forum_Thread as Thread', array('local' => 'thread_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
    }
}

