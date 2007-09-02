<?php
class BoardWithPosition extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('position', 'integer');
        $this->hasColumn('category_id', 'integer');
    }
    public function setUp() {
        $this->hasOne('CategoryWithPosition as Category', 'BoardWithPosition.category_id');
    }
}
