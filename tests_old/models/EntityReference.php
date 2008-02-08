<?php
class EntityReference extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('entity1', 'integer', null, 'primary');
        $class->setColumn('entity2', 'integer', null, 'primary');
    }
}

