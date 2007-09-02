<?php
class DateTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('date', 'date', 20); 
    }
}

