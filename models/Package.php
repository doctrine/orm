<?php
class Package extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('description', 'string', 255);
    }

    public function setUp()
    {
        $this->hasMany('PackageVersion as Version', 'PackageVersion.package_id');
    }
}
