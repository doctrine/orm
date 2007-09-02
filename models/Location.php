<?php
class Location extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('lat', 'double', 10, array ());
        $this->hasColumn('lon', 'double', 10, array ());
    }

    public function setUp()
    {
        $this->hasMany('LocationI18n as LocationI18n', array('local' => 'id', 'foreign' => 'id'));
    }
}
