<?php
class Forum_Category extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('root_category_id', 'integer', 10);
        $class->setColumn('parent_category_id', 'integer', 10);
        $class->setColumn('name', 'string', 50);
        $class->setColumn('description', 'string', 99999);
        $class->hasMany('Forum_Category as Subcategory', array('local' => 'id', 'foreign' => 'parent_category_id'));
        $class->hasOne('Forum_Category as Parent', array('local' => 'parent_category_id', 'foreign' => 'id'));
        $class->hasOne('Forum_Category as Rootcategory', array('local' => 'root_category_id', 'foreign' => 'id'));
    }
}
