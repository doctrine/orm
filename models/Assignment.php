<?php
class Assignment extends Doctrine_Record {
    public function setTableDefinition() {
       $this->hasColumn('task_id', 'integer'); 
       $this->hasColumn('resource_id', 'integer'); 
    } 
}

