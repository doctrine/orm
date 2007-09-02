<?php
class FilterTest2 extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string',100);
        $this->hasColumn('test1_id', 'integer');
    }
}
