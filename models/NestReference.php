<?php
class NestReference extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('parent_id', 'integer', 4, 'primary');
        $class->setColumn('child_id', 'integer', 4, 'primary');
    }
}
