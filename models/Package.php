<?php
class Package extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('description', 'string', 255);
        $class->hasMany('PackageVersion as Version', array('local' => 'id', 'foreign' => 'package_id'));
    }
}
