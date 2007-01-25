<?php
class Book extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('bookName as name', 'string');
    }
}
$book = new Book();
$book->name = 'Some book';
$book->save();
?>
