<?php
class Photo extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 100);
        $class->hasMany('Tag', array('local' => 'photo_id', 'foreign' => 'tag_id', 'refClass' => 'Phototag'));
    }
}
