<?php
class Page extends Doctrine_Record
{

    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', 30);
        $class->setColumn('url', 'string', 100);
    	$class->hasMany('Bookmark as Bookmarks',
                        array('local' => 'id',
                              'foreign' => 'page_id'));
    }
}
