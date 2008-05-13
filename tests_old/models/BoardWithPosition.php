<?php
class BoardWithPosition extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('position', 'integer');
        $class->setColumn('category_id', 'integer');
        $class->hasOne('CategoryWithPosition as Category', array('local' => 'category_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
    }
}
