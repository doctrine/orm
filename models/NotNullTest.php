<?php
class NotNullTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 100, 'notnull');
        $this->hasColumn('type', 'integer', 11);                                     	
    }
}
