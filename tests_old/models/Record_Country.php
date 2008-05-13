<?php
class Record_Country extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 200);
        $class->hasMany('Record_City as City', array('local' => 'id', 'foreign' => 'country_id'));
    }
}


