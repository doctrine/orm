<?php
class CascadeDeleteRelatedTest2 extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string');
        $class->setColumn('cscd_id', 'integer');
        $class->hasOne('CascadeDeleteRelatedTest', array('local' => 'cscd_id',
                                                        'foreign' => 'id',
                                                        'onDelete' => 'SET NULL'));
    }
}
