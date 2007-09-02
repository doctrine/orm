<?php
class Data_File extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('filename', 'string');
        $this->hasColumn('file_owner_id', 'integer');
    }
    public function setUp() {
        $this->hasOne('File_Owner', 'Data_File.file_owner_id');
    }
}
