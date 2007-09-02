<?php
class Log_Status extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 255);
    }
}
