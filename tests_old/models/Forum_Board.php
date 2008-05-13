<?php
class Forum_Board extends Doctrine_Entity { 
    public static function initMetadata($class) {
        $class->setColumn('category_id', 'integer', 10);
        $class->setColumn('name', 'string', 100);
        $class->setColumn('description', 'string', 5000);
        $class->hasOne('Forum_Category as Category', array('local' => 'category_id', 'foreign' => 'id'));
        $class->hasMany('Forum_Thread as Threads',  array('local' => 'id', 'foreign' => 'board_id'));
    }
}

