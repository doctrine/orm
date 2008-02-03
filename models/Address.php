<?php
class Address extends Doctrine_Record 
{
    public static function initMetadata($class)
    {
        $class->setColumn('address', 'string', 200);
        $class->hasMany('User', array('local' => 'address_id', 
                                     'foreign' => 'user_id',
                                     'refClass' => 'EntityAddress'));
    }
}
