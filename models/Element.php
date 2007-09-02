<?php
class Element extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('parent_id', 'integer');
    }
    public function setUp() {
        $this->hasMany('Element as Child', 'Child.parent_id');
        $this->hasOne('Element as Parent', 'Element.parent_id');
    }
}

