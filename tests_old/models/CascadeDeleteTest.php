<?php
class CascadeDeleteTest extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string');
        $class->hasMany('CascadeDeleteRelatedTest as Related', 
                        array('local' => 'id',
                              'foreign' => 'cscd_id'));
    }
}
