<?php
class SelfRefTest extends Doctrine_Entity
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string', 50);
        $class->setColumn('created_by', 'integer');
        $class->hasOne('SelfRefTest as createdBy', array('local' => 'created_by'));
    }
}

