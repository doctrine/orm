<?php
class Book extends Doctrine_Record {
    public function setUp() {
        $this->ownsMany('Author', 'Author.book_id');
    }
    public function setTableDefinition() {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('name', 'string',20);
    }
}
