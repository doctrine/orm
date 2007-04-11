<?php
class Task extends Doctrine_Record {
     public function setUp() {  
        $this->hasOne('Task as Parent','Task.parent_id');
        $this->hasMany('Task as Subtask','Subtask.parent_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('name','string',100);
        $this->hasColumn('parent_id','integer');
    }
}
?>
