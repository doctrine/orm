<?php
class ORM_AccessControl extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string', 255);
        $class->hasMany('ORM_AccessGroup as accessGroups', array(
                'local' => 'accessControlID', 'foreign' => 'accessGroupID',
                'refClass' => 'ORM_AccessControlsGroups')
                );
    }
}
