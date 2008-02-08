<?php
class File_Owner extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 255);
        $class->hasOne('Data_File', array('local' => 'id', 'foreign' => 'file_owner_id'));
    }
}
