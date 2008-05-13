<?php
class Rec2  extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('user_id', 'integer', 10, array (  'unique' => true,));
        $class->setColumn('address', 'string', 150, array ());
        $class->hasOne('Rec1 as User', array('local' => 'user_id', 'foreign' => 'id'));
    }
}
