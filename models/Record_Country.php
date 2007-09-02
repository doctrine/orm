<?php
class Record_Country extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 200);
    }
    public function setUp() {
        $this->hasMany('Record_City as City', 'City.country_id');
    }
}


