<?php
class CascadeDeleteRelatedTest extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string');
        $class->setColumn('cscd_id', 'integer');
        $class->hasOne('CascadeDeleteTest', array('local' => 'cscd_id', 
                                                 'foreign' => 'id',
                                                 'onDelete' => 'CASCADE',
                                                 'onUpdate' => 'SET NULL'));

        $class->hasMany('CascadeDeleteRelatedTest2 as Related',
                        array('local' => 'id',
                              'foreign' => 'cscd_id'));
    }
}
