<?php
class Tag extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('tag', 'string', 100);
        $class->hasMany('Photo', array('local' => 'tag_id', 'foreign' => 'photo_id', 'refClass' => 'Phototag'));
    }
}
