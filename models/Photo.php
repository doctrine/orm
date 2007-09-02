<?php
class Photo extends Doctrine_Record {
    public function setUp() {
        $this->hasMany('Tag', 'Phototag.tag_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 100);
    }
}
