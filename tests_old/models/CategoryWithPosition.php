<?php
class CategoryWithPosition extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('position', 'integer');
        $class->setColumn('name', 'string', 255);
        $class->hasMany('BoardWithPosition as Boards', array('local' => 'id' , 'foreign' => 'category_id')); 
    }
}
