<?php
class CustomPK extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('uid', 'integer',11, 'autoincrement|primary');
        $this->hasColumn('name', 'string',255);
    }
}
