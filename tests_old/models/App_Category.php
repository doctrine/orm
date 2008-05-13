<?php
class App_Category extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 32);
        $class->setColumn('parent_id', 'integer');
        $class->hasMany('App', array('local' => 'id', 'foreign' => 'app_category_id'));
        $class->hasOne('App_Category as Parent', array('local' => 'parent_id', 'foreign' => 'id'));
    }
}
