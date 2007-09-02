<?php
class Log_Entry extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('stamp', 'timestamp');
        $this->hasColumn('status_id', 'integer');
    }
    public function setUp() {
        $this->hasOne('Log_Status', 'Log_Entry.status_id');
    }
}
