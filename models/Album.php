<?php
class Album extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('user_id', 'integer');
        $class->setColumn('name', 'string',20);
        $class->hasMany('Song', array('local' => 'id', 'foreign' => 'album_id'));
        $class->hasOne('User', array('local' => 'user_id',
                                    'foreign' => 'id',
                                    'onDelete' => 'CASCADE'));
    }
}

