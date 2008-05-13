<?php
class ORM_AccessControlsGroups extends Doctrine_Entity 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('accessControlID', 'integer', 11, array('primary' => true)); 
        $class->setColumn('accessGroupID', 'integer', 11, array('primary' => true));
    }
}
