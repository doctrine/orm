<?php
class Record_City extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('country_id', 'integer');
        $this->hasColumn('district_id', 'integer');
    }
    public function setUp() {
        $this->hasOne('Record_Country as Country', 'Record_City.country_id');
        $this->hasOne('Record_District as District', 'Record_City.district_id');
    }
}
