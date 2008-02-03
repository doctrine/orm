<?php
class ORM_AccessGroup extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string', 255);
        $class->hasMany('ORM_AccessControl as accessControls',
                array('local' => 'accessGroupID', 'foreign' => 'accessControlID',
                        'refClass' => 'ORM_AccessControlsGroups'));
    }
}
