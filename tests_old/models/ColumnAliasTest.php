<?php
class ColumnAliasTest extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('column1 as alias1', 'string', 200);
        $class->setColumn('column2 as alias2', 'integer', 4);
        $class->setColumn('another_column as anotherField', 'string', 50);
        $class->setColumn('book_id as bookId', 'integer', 4);
        $class->hasOne('Book as book', array('local' => 'book_id', 'foreign' => 'id'));
    }
}
