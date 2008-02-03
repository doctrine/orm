<?php
class Forum_Thread extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('board_id', 'integer', 10);
        $class->setColumn('updated', 'integer', 10);
        $class->setColumn('closed', 'integer', 1);
        $class->hasOne('Forum_Board as Board', array('local' => 'board_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
        $class->hasMany('Forum_Entry as Entries', array('local' => 'id', 'foreign' => 'thread_id'));
    }
}

