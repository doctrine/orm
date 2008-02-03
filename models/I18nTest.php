<?php
class I18nTest extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', 200);
        $class->setColumn('title', 'string', 200);
        $class->actAs('I18n', array('fields' => array('name', 'title')));
    }
}
