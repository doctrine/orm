<?php
class ColumnAliasTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('column1 as alias1', 'string', 200);
        $this->hasColumn('column2 as alias2', 'integer', 4);
        $this->hasColumn('another_column as anotherField', 'string', 50);
        $this->hasColumn('book_id as bookId', 'integer', 4);
    }
    public function setUp()
    {
        $this->hasOne('Book as book', array('local' => 'book_id', 'foreign' => 'id'));
    }
}
