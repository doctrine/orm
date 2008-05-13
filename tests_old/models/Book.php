<?php
class Book extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('user_id', 'integer');
        $class->setColumn('name', 'string',20);
        $class->hasMany('Author', array('local' => 'id', 'foreign' => 'book_id'));
        $class->hasOne('User', array('local' => 'user_id',
                                    'foreign' => 'id',
                                    'onDelete' => 'CASCADE'));
    }
}
