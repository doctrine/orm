<?php
class FilterTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string',100);
    }
    public function setUp() {
        $this->ownsMany('FilterTest2 as filtered', 'FilterTest2.test1_id');
    }
}
