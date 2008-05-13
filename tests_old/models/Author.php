<?php
class Author extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('book_id', 'integer');
        $class->setColumn('name', 'string',20);
        $class->hasOne('Book', array('local' => 'book_id',
                                    'foreign' => 'id',
                                    'onDelete' => 'CASCADE'));
    }
}
