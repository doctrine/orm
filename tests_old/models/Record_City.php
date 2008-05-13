<?php
class Record_City extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 200);
        $class->setColumn('country_id', 'integer');
        $class->setColumn('district_id', 'integer');
        $class->hasOne('Record_Country as Country', array('local' => 'country_id', 'foreign' => 'id'));
        $class->hasOne('Record_District as District', array('local' => 'district_id', 'foreign' => 'id'));
    }
}
