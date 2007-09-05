<?php
class Author extends Doctrine_Record
{
    public function setUp()
    {
        $this->hasOne('Book', array('local' => 'book_id',
                                    'foreign' => 'id',
                                    'onDelete' => 'CASCADE'));
    }
    public function setTableDefinition()
    {
        $this->hasColumn('book_id', 'integer');
        $this->hasColumn('name', 'string',20);
    }
}
