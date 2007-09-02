<?php
class CategoryWithPosition extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('position', 'integer');
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp() {
        $this->ownsMany('BoardWithPosition as Boards', 'BoardWithPosition.category_id');   
    }   
}
