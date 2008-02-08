<?php
class SearchTest extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('title', 'string', 100);
        $class->setColumn('content', 'string');
        $options = array('generateFiles' => false,
                         'fields' => array('title', 'content'));
        $class->actAs('Searchable', $options);
    }
}
