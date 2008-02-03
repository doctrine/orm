<?php
class Rec1 extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('first_name', 'string', 128, array ());
        $class->hasOne('Rec2 as Account', array('local' => 'id', 'foreign' => 'user_id'));
    }
}


