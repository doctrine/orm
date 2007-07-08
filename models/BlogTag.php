<?php 
class BlogTag extends Doctrine_Record
{
    public function setUp() {
        $this->hasMany('Photo', 'Phototag.photo_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('tag', 'string', 100);
    }
}
