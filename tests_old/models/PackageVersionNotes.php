<?php
class PackageVersionNotes extends Doctrine_Entity 
{
    public function setTableDefinition()
    {
        $this->hasColumn('package_version_id', 'integer');
        $this->hasColumn('description', 'string', 255);
    }
    public function setUp()
    {
        $this->hasOne('PackageVersion', 'PackageVersionNotes.package_version_id');
    }
}
