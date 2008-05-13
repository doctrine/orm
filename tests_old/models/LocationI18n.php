<?php
class LocationI18n extends Doctrine_Entity
{ 
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', 50, array());
        $class->setColumn('id', 'integer', 10, array('primary' => true));
        $class->setColumn('culture', 'string', 2);
        $class->hasOne('Location as Location', array('local' => 'id'));
    }
}
