<?php
class Author extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('book_id', 'integer');
        $this->hasColumn('name', 'string',20);
    }
}
