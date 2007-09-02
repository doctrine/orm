<?php
class Package extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('description', 'string', 255);
    }

    public function setUp()
    {
        $this->ownsMany('PackageVersion as Version', 'PackageVersion.package_id');
    }
}
