<?php
class PackageVersion extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('package_id', 'integer');
        $this->hasColumn('description', 'string', 255);
    }
    public function setUp()
    {
        $this->hasOne('Package', 'PackageVersion.package_id');
        $this->hasMany('PackageVersionNotes as Note', 'PackageVersionNotes.package_version_id');
    }
}
