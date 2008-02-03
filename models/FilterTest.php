<?php
class FilterTest extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string',100);
        $class->hasMany('FilterTest2 as filtered', array('local' => 'id', 'foreign' => 'test1_id'));
    }
}
