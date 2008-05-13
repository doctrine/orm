<?php
class ForumBoard extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->mapColumn('position', 'integer');
        $class->mapColumn('category_id', 'integer');
        $class->hasOne('ForumCategory as category',
                array('local' => 'category_id', 'foreign' => 'id'));
    }
}
