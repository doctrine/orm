<?php
class TreeLeaf extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
    	$class->setColumn('name', 'string');
        $class->setColumn('parent_id', 'integer');
        $class->hasOne('TreeLeaf as Parent', array('local' => 'parent_id', 'foreign' => 'id'));
        $class->hasMany('TreeLeaf as Children', array('local' => 'id', 'foreign' => 'parent_id'));
    }
}
