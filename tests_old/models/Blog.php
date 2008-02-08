<?php
class Blog extends Doctrine_Record
{
    public static function initMetadata($class)
    {
    	$class->loadTemplate('Taggable');
    }
}
class Taggable extends Doctrine_Template
{
    public static function initMetadata($class)
    {
        //$this->hasMany('[Component]TagTemplate as Tag');
    }
}
class TagTemplate extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', 100);
        $class->setColumn('description', 'string');
    }
}
