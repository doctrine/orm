<?php
class EntityAddress extends Doctrine_Entity 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('user_id', 'integer', null, array('primary' => true));
        $class->setColumn('address_id', 'integer', null, array('primary' => true));
    }
}
