<?php
class Location extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('lat', 'double', 10, array ());
        $class->setColumn('lon', 'double', 10, array ());
        $class->hasMany('LocationI18n as LocationI18n', array('local' => 'id', 'foreign' => 'id'));
    }
}
