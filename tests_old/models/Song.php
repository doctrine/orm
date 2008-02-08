<?php
class Song extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('album_id', 'integer');
        $class->setColumn('genre', 'string',20);
        $class->setColumn('title', 'string',30);
        $class->hasOne('Album', array('local' => 'album_id',
                                     'foreign' => 'id',
                                     'onDelete' => 'CASCADE'));
    }
}
