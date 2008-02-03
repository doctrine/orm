<?php
class Element extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 100);
        $class->setColumn('parent_id', 'integer');
        $class->hasMany('Element as Child', array('local' => 'id', 'foreign' => 'parent_id'));
        $class->hasOne('Element as Parent', array('local' => 'parent_id', 'foreign' => 'id'));
    }
}

