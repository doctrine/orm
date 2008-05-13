<?php
class NestReference extends Doctrine_Entity 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('parent_id', 'integer', 4, array('primary' => true));
        $class->setColumn('child_id', 'integer', 4, array('primary' => true));
    }
}
