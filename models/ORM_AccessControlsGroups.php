<?php
class ORM_AccessControlsGroups extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('accessControlID', 'integer', 11, array('primary' => true)); 
        $this->hasColumn('accessGroupID', 'integer', 11, array('primary' => true));
    }
}
