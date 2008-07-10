<?php
class ForumCategory extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->mapColumn('id', 'integer', 4, array('primary'));
        $class->mapColumn('position', 'integer');
        $class->mapColumn('name', 'string', 255);
        $class->hasMany('ForumBoard as boards', array(
                'local' => 'id' , 'foreign' => 'category_id')); 
    }
}
