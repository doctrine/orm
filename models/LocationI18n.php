<?php
class LocationI18n extends Doctrine_Record
{ 
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 50, array());
        $this->hasColumn('id', 'integer', 10, array('primary' => true));
        $this->hasColumn('culture', 'string', 2);
    }
    
    public function setUp()
    {
        $this->hasOne('Location as Location', array('local' => 'id'));
    }
}
