<?php
class File_Owner extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 255);
    }
	public function setUp() {
        $this->hasOne('Data_File', 'Data_File.file_owner_id');
    }
}
